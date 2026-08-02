import fs from "node:fs";
import nodemailer from "nodemailer";
import { JWT } from "google-auth-library";

const GMAIL_SEND_SCOPE = "https://www.googleapis.com/auth/gmail.send";

function base64url(buffer) {
  return buffer.toString("base64").replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
}

export function createGmailApiMailer(config, dependencies = {}) {
  const credentials = dependencies.credentials ?? JSON.parse(fs.readFileSync(config.googleServiceAccountFile, "utf8"));
  if (!credentials.client_email || !credentials.private_key) throw new Error("Google service-account file is missing required credentials");
  const auth = dependencies.auth ?? new JWT({
    email: credentials.client_email,
    key: credentials.private_key,
    scopes: [GMAIL_SEND_SCOPE],
    subject: config.gmailUser,
  });
  const composer = nodemailer.createTransport({
    streamTransport: true,
    buffer: true,
    newline: "unix",
  });

  async function accessToken() {
    const token = await auth.getAccessToken();
    if (!token.token) throw new Error("Google did not issue an access token");
    return token.token;
  }

  return {
    async verify() {
      await accessToken();
      return true;
    },
    async sendMail(message) {
      const composed = await composer.sendMail(message);
      const response = await (dependencies.fetch ?? fetch)(`https://gmail.googleapis.com/gmail/v1/users/${encodeURIComponent(config.gmailUser)}/messages/send`, {
        method: "POST",
        headers: {
          authorization: `Bearer ${await accessToken()}`,
          "content-type": "application/json; charset=utf-8",
        },
        body: JSON.stringify({ raw: base64url(composed.message) }),
        signal: AbortSignal.timeout(30_000),
      });
      const body = await response.text();
      let result = {};
      try { result = JSON.parse(body); } catch { /* sanitized below */ }
      if (!response.ok) {
        const error = new Error("Workspace Gmail API rejected the message");
        error.responseCode = response.status;
        error.response = typeof result?.error?.status === "string" ? result.error.status : "";
        throw error;
      }
      return { response: `${response.status} accepted`, messageId: result.id || null };
    },
  };
}
