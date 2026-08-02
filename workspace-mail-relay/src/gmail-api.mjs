import fs from "node:fs";
import nodemailer from "nodemailer";
import { OAuth2Client } from "google-auth-library";

function base64url(buffer) {
  return buffer.toString("base64").replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/g, "");
}

export function createGmailApiMailer(config, dependencies = {}) {
  const clientFile = dependencies.clientFile ?? JSON.parse(fs.readFileSync(config.googleOauthClientFile, "utf8"));
  const client = clientFile.installed ?? clientFile.web;
  if (!client?.client_id || !client?.client_secret) throw new Error("Google OAuth client file is missing required credentials");
  const tokens = dependencies.tokens ?? JSON.parse(fs.readFileSync(config.googleOauthTokenFile, "utf8"));
  if (!tokens.refresh_token) throw new Error("Google OAuth token file is missing its refresh token");
  const auth = dependencies.auth ?? new OAuth2Client(client.client_id, client.client_secret);
  auth.setCredentials(tokens);
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
      const response = await (dependencies.fetch ?? fetch)("https://gmail.googleapis.com/gmail/v1/users/me/messages/send", {
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
