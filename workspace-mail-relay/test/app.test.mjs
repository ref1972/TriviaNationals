import assert from "node:assert/strict";
import crypto from "node:crypto";
import test from "node:test";
import { createServer } from "../src/app.mjs";

const secret = "a".repeat(48);
const clientHashes = new Map([["timed_quiz", crypto.createHash("sha256").update(secret).digest("hex")]]);

function fixture(overrides = {}) {
  const records = [];
  let accepted = overrides.accepted ?? 0;
  let lastAcceptedAt = overrides.lastAcceptedAtMs ?? 0;
  const sent = [];
  const config = {
    clientHashes,
    dailySafetyLimit: overrides.limit ?? 100,
    hourlySafetyLimit: overrides.hourlyLimit ?? 100,
    minSendIntervalMs: overrides.minSendIntervalMs ?? 1,
    fromEmail: "info@trivianationals.org",
    fromName: "Trivia Nationals",
    replyTo: "info@trivianationals.org",
  };
  const store = {
    acceptedLast24Hours: () => accepted,
    acceptedLastHour: () => overrides.acceptedHour ?? accepted,
    lastAcceptedAtMs: () => lastAcceptedAt,
    record: (entry) => {
      records.push(entry);
      if (entry.accepted) {
        accepted += 1;
        lastAcceptedAt = Date.now();
      }
    },
  };
  const mailer = {
    verify: async () => true,
    sendMail: async (message) => {
      sent.push(message);
      if (overrides.sendError) throw overrides.sendError;
      return { response: "250 2.0.0 OK", messageId: "provider-id" };
    },
  };
  return { server: createServer({ config, store, mailer }), records, sent };
}

async function listen(server) {
  await new Promise((resolve) => server.listen(0, "127.0.0.1", resolve));
  const address = server.address();
  return `http://127.0.0.1:${address.port}`;
}

async function post(url, body, credentials = true) {
  return fetch(`${url}/v1/mail`, {
    method: "POST",
    headers: {
      "content-type": "application/json",
      ...(credentials ? { "x-relay-client": "timed_quiz", authorization: `Bearer ${secret}` } : {}),
    },
    body: JSON.stringify(body),
  });
}

test("rejects unauthenticated requests", async (t) => {
  const { server, sent } = fixture();
  t.after(() => server.close());
  const url = await listen(server);
  const response = await post(url, { action: "send_email" }, false);
  assert.equal(response.status, 401);
  assert.equal(sent.length, 0);
});

test("reports local rolling capacity without contacting SMTP", async (t) => {
  const { server, sent } = fixture({ accepted: 12, limit: 100 });
  t.after(() => server.close());
  const url = await listen(server);
  const response = await post(url, { action: "email_quota" });
  assert.equal(response.status, 200);
  assert.deepEqual(await response.json(), { ok: true, action: "email_quota", accepted_24h: 12, accepted_1h: 12, remaining: 88, capacity_type: "local_safety_limit" });
  assert.equal(sent.length, 0);
});

test("sends one personalized recipient and records only its hash", async (t) => {
  const { server, sent, records } = fixture();
  t.after(() => server.close());
  const url = await listen(server);
  const response = await post(url, { action: "send_email", to: "Player@example.com", subject: "Invitation", html_body: "<p>Private link</p>", plain_body: "Private link" });
  assert.equal(response.status, 200);
  assert.equal((await response.json()).message_id, "provider-id");
  assert.equal(sent.length, 1);
  assert.equal(sent[0].to, "player@example.com");
  assert.deepEqual(sent[0].envelope, { from: "info@trivianationals.org", to: ["player@example.com"] });
  assert.equal(records.length, 1);
  assert.equal(records[0].accepted, true);
  assert.equal(records[0].recipientHash, crypto.createHash("sha256").update("player@example.com").digest("hex"));
  assert.equal(JSON.stringify(records).includes("Private link"), false);
});

test("serializes concurrent callers and enforces the global send interval", async (t) => {
  const { server, sent } = fixture({ minSendIntervalMs: 30 });
  t.after(() => server.close());
  const url = await listen(server);
  const started = Date.now();
  const responses = await Promise.all([
    post(url, { action: "send_email", to: "one@example.com", subject: "One", plain_body: "Hello" }),
    post(url, { action: "send_email", to: "two@example.com", subject: "Two", plain_body: "Hello" }),
  ]);
  assert.deepEqual(responses.map((response) => response.status), [200, 200]);
  assert.equal(sent.length, 2);
  assert.ok(Date.now() - started >= 25);
});

test("stops at the local safety limit before SMTP", async (t) => {
  const { server, sent, records } = fixture({ accepted: 100, limit: 100 });
  t.after(() => server.close());
  const url = await listen(server);
  const response = await post(url, { action: "send_email", to: "player@example.com", subject: "Invitation", plain_body: "Hello" });
  assert.equal(response.status, 429);
  assert.equal((await response.json()).quota_exhausted, true);
  assert.equal(sent.length, 0);
  assert.equal(records.length, 0);
});

test("stops at the rolling hourly safety limit before SMTP", async (t) => {
  const { server, sent, records } = fixture({ accepted: 25, acceptedHour: 10, hourlyLimit: 10 });
  t.after(() => server.close());
  const url = await listen(server);
  const response = await post(url, { action: "send_email", to: "player@example.com", subject: "Invitation", plain_body: "Hello" });
  assert.equal(response.status, 429);
  assert.match((await response.json()).error, /hourly/);
  assert.equal(sent.length, 0);
  assert.equal(records.length, 0);
});

test("rejects header injection before SMTP", async (t) => {
  const { server, sent, records } = fixture();
  t.after(() => server.close());
  const url = await listen(server);
  const response = await post(url, { action: "send_email", to: "player@example.com", subject: "Invitation\r\nBcc: bad@example.com", plain_body: "Hello" });
  assert.equal(response.status, 400);
  assert.equal(sent.length, 0);
  assert.equal(records.length, 0);
});

test("SMTP rejection is sanitized, audited, and not accepted", async (t) => {
  const error = Object.assign(new Error("550 5.7.1 private provider detail"), { responseCode: 550, response: "550 5.7.1 rejected" });
  const { server, records } = fixture({ sendError: error });
  t.after(() => server.close());
  const url = await listen(server);
  const response = await post(url, { action: "send_email", to: "player@example.com", subject: "Invitation", plain_body: "Hello" });
  assert.equal(response.status, 502);
  const body = await response.json();
  assert.equal(body.error, "Workspace SMTP relay did not accept the message");
  assert.equal(body.smtp_code, 550);
  assert.equal(body.enhanced_status, "5.7.1");
  assert.equal(body.retryable, false);
  assert.equal(records[0].accepted, false);
});
