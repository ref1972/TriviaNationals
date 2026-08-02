import crypto from "node:crypto";
import http from "node:http";

const MAX_BODY_BYTES = 300_000;
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function json(res, status, body) {
  const data = Buffer.from(JSON.stringify(body));
  res.writeHead(status, {
    "content-type": "application/json; charset=utf-8",
    "content-length": data.length,
    "cache-control": "no-store",
    "x-content-type-options": "nosniff",
  });
  res.end(data);
}

function digest(value) {
  return crypto.createHash("sha256").update(value).digest("hex");
}

function safeEqualHex(actual, expected) {
  if (!/^[a-f0-9]{64}$/.test(actual) || !/^[a-f0-9]{64}$/.test(expected)) return false;
  return crypto.timingSafeEqual(Buffer.from(actual, "hex"), Buffer.from(expected, "hex"));
}

function authenticate(req, config) {
  const clientId = String(req.headers["x-relay-client"] || "").trim();
  const authorization = String(req.headers.authorization || "");
  const secret = authorization.startsWith("Bearer ") ? authorization.slice(7) : "";
  const expected = config.clientHashes.get(clientId);
  if (!expected || secret.length < 32 || !safeEqualHex(digest(secret), expected)) return null;
  return clientId;
}

async function readJson(req) {
  const chunks = [];
  let size = 0;
  for await (const chunk of req) {
    size += chunk.length;
    if (size > MAX_BODY_BYTES) throw Object.assign(new Error("Request body is too large"), { statusCode: 413 });
    chunks.push(chunk);
  }
  try {
    return JSON.parse(Buffer.concat(chunks).toString("utf8"));
  } catch {
    throw Object.assign(new Error("Request body must be valid JSON"), { statusCode: 400 });
  }
}

function deliveryDetails(error) {
  const responseCode = Number.isInteger(error?.responseCode) ? error.responseCode : null;
  const response = typeof error?.response === "string" ? error.response : "";
  const enhanced = response.match(/\b([245]\.\d\.\d)\b/)?.[1] || null;
  return {
    providerCode: responseCode,
    enhancedStatus: enhanced,
    errorClass: responseCode ? `provider_${responseCode}` : "transport_error",
    quotaExhausted: responseCode === 429 || /resource_exhausted|rate.?limit|quota/i.test(response),
  };
}

export function createServer({ config, store, mailer }) {
  // One global queue enforces pacing across every caller, even if Timed Quiz
  // and WordPress try to send concurrently.
  let sendQueue = Promise.resolve();
  let lastSendStartedAtMs = store.lastAcceptedAtMs();
  async function serializeSend(operation) {
    const predecessor = sendQueue;
    let release;
    sendQueue = new Promise((resolve) => { release = resolve; });
    await predecessor;
    try {
      const waitMs = Math.max(0, lastSendStartedAtMs + config.minSendIntervalMs - Date.now());
      if (waitMs) await new Promise((resolve) => setTimeout(resolve, waitMs));
      lastSendStartedAtMs = Date.now();
      return await operation();
    } finally {
      release();
    }
  }

  return http.createServer(async (req, res) => {
    if (req.method === "GET" && req.url === "/health") {
      return json(res, 200, { ok: true, service: "trivia-workshop-mail-relay", release: config.releaseId ?? "development" });
    }
    if (req.method !== "POST" || req.url !== "/v1/mail") return json(res, 404, { ok: false, error: "Not found" });

    const clientId = authenticate(req, config);
    if (!clientId) return json(res, 401, { ok: false, error: "Unauthorized" });

    try {
      const body = await readJson(req);
      if (body.action === "verify") {
        await mailer.verify();
        const accepted = store.acceptedLast24Hours();
        return json(res, 200, { ok: true, action: "verify", accepted_24h: accepted, accepted_1h: store.acceptedLastHour(), remaining: Math.max(0, config.dailySafetyLimit - accepted) });
      }
      if (body.action === "email_quota") {
        const accepted = store.acceptedLast24Hours();
        return json(res, 200, { ok: true, action: "email_quota", accepted_24h: accepted, accepted_1h: store.acceptedLastHour(), remaining: Math.max(0, config.dailySafetyLimit - accepted), capacity_type: "local_safety_limit" });
      }
      if (body.action !== "send_email") return json(res, 400, { ok: false, error: "Unknown action" });

      const to = String(body.to || "").trim().toLowerCase();
      const subject = String(body.subject || "").trim();
      const html = String(body.html_body || "");
      const plain = String(body.plain_body || "");
      if (!EMAIL_PATTERN.test(to) || to.length > 254) return json(res, 400, { ok: false, error: "Invalid recipient" });
      if (!subject || subject.length > 200 || /[\r\n]/.test(subject)) return json(res, 400, { ok: false, error: "Invalid subject" });
      if ((!html && !plain) || html.length > 200_000 || plain.length > 100_000) return json(res, 400, { ok: false, error: "Invalid message body" });

      return await serializeSend(async () => {
        const accepted = store.acceptedLast24Hours();
        const acceptedHour = store.acceptedLastHour();
        if (accepted >= config.dailySafetyLimit || acceptedHour >= config.hourlySafetyLimit) {
          return json(res, 429, {
            ok: false,
            error: accepted >= config.dailySafetyLimit ? "Relay 24-hour safety limit reached" : "Relay hourly safety limit reached",
            quota_exhausted: true,
            remaining: Math.max(0, config.dailySafetyLimit - accepted),
            capacity_type: "local_safety_limit",
          });
        }

        try {
          const info = await mailer.sendMail({
          envelope: { from: config.fromEmail, to: [to] },
          from: { name: config.fromName, address: config.fromEmail },
          replyTo: config.replyTo,
          to,
          subject,
          text: plain || undefined,
          html: html || undefined,
          disableFileAccess: true,
          disableUrlAccess: true,
        });
          const providerCode = Number(String(info.response || "").match(/^([0-9]{3})/)?.[1] || 200);
          store.record({ clientId, recipientHash: digest(to), accepted: true, providerCode, messageId: info.messageId || null });
          return json(res, 200, {
            ok: true,
            action: "send_email",
            remaining: Math.max(0, config.dailySafetyLimit - accepted - 1),
            capacity_type: "local_safety_limit",
            message_id: info.messageId || null,
          });
        } catch (error) {
          const details = deliveryDetails(error);
          store.record({ clientId, recipientHash: digest(to), accepted: false, ...details });
          return json(res, 502, {
            ok: false,
            error: "Workspace Gmail API did not accept the message",
            retryable: !details.providerCode || details.providerCode === 429 || details.providerCode >= 500,
            quota_exhausted: details.quotaExhausted,
            provider_code: details.providerCode,
            enhanced_status: details.enhancedStatus,
            remaining: Math.max(0, config.dailySafetyLimit - accepted),
          });
        }
      });
    } catch (error) {
      return json(res, error?.statusCode || 502, { ok: false, error: error instanceof Error ? error.message : "Relay request failed" });
    }
  });
}
