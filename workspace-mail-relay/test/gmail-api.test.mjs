import assert from "node:assert/strict";
import test from "node:test";
import { createGmailApiMailer } from "../src/gmail-api.mjs";

const config = {
  googleServiceAccountFile: "/not/read/in/tests.json",
  gmailUser: "info@trivianationals.org",
};
const credentials = { client_email: "relay@test-project.iam.gserviceaccount.com", private_key: "test-key" };

test("Gmail API mailer verifies delegated credentials without sending", async () => {
  let tokenCalls = 0;
  let fetchCalls = 0;
  const mailer = createGmailApiMailer(config, {
    credentials,
    auth: { getAccessToken: async () => { tokenCalls += 1; return { token: "access-token" }; } },
    fetch: async () => { fetchCalls += 1; return new Response(); },
  });
  assert.equal(await mailer.verify(), true);
  assert.equal(tokenCalls, 1);
  assert.equal(fetchCalls, 0);
});

test("Gmail API mailer creates a single-recipient RFC message and sends over HTTPS", async () => {
  let request;
  const mailer = createGmailApiMailer(config, {
    credentials,
    auth: { getAccessToken: async () => ({ token: "access-token" }) },
    fetch: async (url, init) => {
      request = { url, init };
      return new Response(JSON.stringify({ id: "gmail-message-id" }), { status: 200, headers: { "content-type": "application/json" } });
    },
  });
  const result = await mailer.sendMail({
    envelope: { from: "info@trivianationals.org", to: ["player@example.com"] },
    from: { name: "Trivia Nationals", address: "info@trivianationals.org" },
    replyTo: "info@trivianationals.org",
    to: "player@example.com",
    subject: "Invitation",
    text: "Private link",
    html: "<p>Private link</p>",
    disableFileAccess: true,
    disableUrlAccess: true,
  });
  assert.equal(result.messageId, "gmail-message-id");
  assert.match(request.url, /gmail\.googleapis\.com\/gmail\/v1\/users\/info%40trivianationals\.org\/messages\/send$/);
  assert.equal(request.init.headers.authorization, "Bearer access-token");
  const raw = JSON.parse(request.init.body).raw.replace(/-/g, "+").replace(/_/g, "/");
  const message = Buffer.from(raw, "base64").toString("utf8");
  assert.match(message, /To: player@example\.com/);
  assert.match(message, /Subject: Invitation/);
  assert.match(message, /Private link/);
});

test("Gmail API errors expose only status metadata", async () => {
  const mailer = createGmailApiMailer(config, {
    credentials,
    auth: { getAccessToken: async () => ({ token: "access-token" }) },
    fetch: async () => new Response(JSON.stringify({ error: { status: "RESOURCE_EXHAUSTED", message: "private provider detail" } }), { status: 429 }),
  });
  await assert.rejects(
    mailer.sendMail({ from: "info@trivianationals.org", to: "player@example.com", subject: "Invitation", text: "Hello" }),
    (error) => error.message === "Workspace Gmail API rejected the message" && error.responseCode === 429 && error.response === "RESOURCE_EXHAUSTED",
  );
});
