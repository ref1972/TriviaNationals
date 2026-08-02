import crypto from "node:crypto";
import fs from "node:fs";
import http from "node:http";
import { OAuth2Client } from "google-auth-library";

const [clientPath, tokenPath] = process.argv.slice(2);
if (!clientPath || !tokenPath) {
  console.error("Usage: node scripts/authorize-gmail.mjs <oauth-client.json> <token-output.json>");
  process.exit(2);
}

const clientFile = JSON.parse(fs.readFileSync(clientPath, "utf8"));
const client = clientFile.installed ?? clientFile.web;
if (!client?.client_id || !client?.client_secret) throw new Error("OAuth client JSON is invalid");

const port = 53682;
const redirectUri = `http://127.0.0.1:${port}/oauth2/callback`;
const oauth = new OAuth2Client(client.client_id, client.client_secret, redirectUri);
const state = crypto.randomBytes(24).toString("base64url");
const url = oauth.generateAuthUrl({
  access_type: "offline",
  prompt: "consent",
  scope: ["https://www.googleapis.com/auth/gmail.send"],
  state,
});

const server = http.createServer(async (req, res) => {
  const requestUrl = new URL(req.url, redirectUri);
  if (requestUrl.pathname !== "/oauth2/callback" || requestUrl.searchParams.get("state") !== state) {
    res.writeHead(400, { "content-type": "text/plain; charset=utf-8" });
    return res.end("Invalid authorization response.");
  }
  const code = requestUrl.searchParams.get("code");
  if (!code) {
    res.writeHead(400, { "content-type": "text/plain; charset=utf-8" });
    return res.end("Google did not return an authorization code.");
  }
  try {
    const { tokens } = await oauth.getToken(code);
    if (!tokens.refresh_token) throw new Error("Google did not return a refresh token");
    fs.writeFileSync(tokenPath, `${JSON.stringify(tokens, null, 2)}\n`, { mode: 0o600, flag: "wx" });
    res.writeHead(200, { "content-type": "text/plain; charset=utf-8" });
    res.end("Authorization complete. You can close this tab and return to Codex.");
    console.log(`TOKEN_SAVED=${tokenPath}`);
    server.close();
  } catch (error) {
    res.writeHead(500, { "content-type": "text/plain; charset=utf-8" });
    res.end("Authorization could not be completed. Return to Codex.");
    console.error(error instanceof Error ? error.message : "Authorization failed");
    server.close(() => { process.exitCode = 1; });
  }
});

server.listen(port, "127.0.0.1", () => {
  console.log(`AUTH_URL=${url}`);
  console.log("Waiting for the Google authorization response...");
});
