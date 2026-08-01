const origin = process.env.APP_ORIGIN ?? "http://localhost:3100";
const adminCredential = process.env.ADMIN_PASSWORD;
if (!adminCredential) throw new Error("ADMIN_PASSWORD is required for the smoke test");

function cookieFrom(response: Response) {
  const value = response.headers.get("set-cookie")?.split(";", 1)[0];
  if (!value) throw new Error("Expected a session cookie");
  return value;
}

async function postForm(url: string, data: Record<string, string>, cookie?: string) {
  return fetch(`${origin}${url}`, {
    method: "POST",
    redirect: "manual",
    headers: { "content-type": "application/x-www-form-urlencoded", ...(cookie ? { cookie } : {}) },
    body: new URLSearchParams(data),
  });
}

const health = await fetch(`${origin}/health`);
if (!health.ok) throw new Error("Health check failed");

const login = await postForm("/admin/login", { ["password"]: adminCredential });
if (login.status !== 302) throw new Error(`Admin login failed: ${login.status}`);
const adminCookie = cookieFrom(login);

const questions = Array.from({ length: 50 }, (_, i) => ({
  position: i + 1,
  prompt: `Smoke-test question ${i + 1}?`,
  answer: `answer ${i + 1}`,
  aliases: [`alias ${i + 1}`],
}));
const importQuestions = await postForm("/admin/questions", { questions: JSON.stringify(questions) }, adminCookie);
if (![302, 409].includes(importQuestions.status)) throw new Error(`Question import failed: ${importQuestions.status}`);

const email = `smoke-${Date.now()}@example.com`;
const importPlayer = await postForm("/admin/players", { players: `${email},Smoke Tester` }, adminCookie);
const linksPage = await importPlayer.text();
const invitation = linksPage.match(/https?:\/\/[^<\n]+\/invite\/[A-Za-z0-9_-]+/)?.[0];
if (!invitation) throw new Error("Invitation link was not generated");

const redeem = await fetch(invitation, { redirect: "manual" });
if (redeem.status !== 302) throw new Error(`Invitation redemption failed: ${redeem.status}`);
const playerCookie = cookieFrom(redeem);
const playerHeaders = { cookie: playerCookie, "content-type": "application/json" };

let state = await fetch(`${origin}/api/state`, { headers: playerHeaders }).then(r => r.json());
if (state.state !== "prestart") throw new Error(`Expected prestart, got ${state.state}`);
await fetch(`${origin}/api/ready`, { method: "POST", headers: playerHeaders, body: "{}" });
state = await fetch(`${origin}/api/state`, { headers: playerHeaders }).then(r => r.json());
if (state.state !== "question" || state.position !== 1) throw new Error("Question 1 was not served");
await fetch(`${origin}/api/draft`, { method: "POST", headers: playerHeaders, body: JSON.stringify({ nonce: state.nonce, sequence: 1, text: "answer 1" }) });
await fetch(`${origin}/api/submit`, { method: "POST", headers: playerHeaders, body: JSON.stringify({ nonce: state.nonce, text: "answer 1" }) });
state = await fetch(`${origin}/api/state`, { headers: playerHeaders }).then(r => r.json());
if (state.state !== "ready" || state.nextPosition !== 2) throw new Error("Answer did not advance to Ready for Q2");

await fetch(`${origin}/api/ready`, { method: "POST", headers: playerHeaders, body: "{}" });
state = await fetch(`${origin}/api/state`, { headers: playerHeaders }).then(r => r.json());
await fetch(`${origin}/api/submit`, { method: "POST", headers: playerHeaders, body: JSON.stringify({ nonce: state.nonce, text: "near miss" }) });
let results = await fetch(`${origin}/admin/results.csv`, { headers: { cookie: adminCookie } }).then(r => r.text());
if (!results.includes(`"${email}","Smoke Tester",1,in_progress,0`)) throw new Error("Correct answer was not reflected in results");

await postForm("/admin/review", { questionId: "2", answer: "near miss", verdict: "correct", note: "Smoke review" }, adminCookie);
results = await fetch(`${origin}/admin/results.csv`, { headers: { cookie: adminCookie } }).then(r => r.text());
if (!results.includes(`"${email}","Smoke Tester",2,in_progress,0`)) throw new Error("Review ruling was not reflected in results");

const adminPage = await fetch(`${origin}/admin`, { headers: { cookie: adminCookie } }).then(r => r.text());
const row = adminPage.slice(adminPage.indexOf(email));
const playerId = row.match(/name="playerId" value="(\d+)"/)?.[1];
if (!playerId) throw new Error("Could not locate player for restart");
await postForm("/admin/restart", { playerId, reason: "Smoke-test restart" }, adminCookie);
state = await fetch(`${origin}/api/state`, { headers: playerHeaders }).then(r => r.json());
if (state.state !== "prestart") throw new Error("Restart did not create a clean prestart state");

await fetch(`${origin}/api/ready`, { method: "POST", headers: playerHeaders, body: "{}" });
state = await fetch(`${origin}/api/state`, { headers: playerHeaders }).then(r => r.json());
await fetch(`${origin}/api/draft`, { method: "POST", headers: playerHeaders, body: JSON.stringify({ nonce: state.nonce, sequence: 1, text: "answer 1" }) });
await new Promise(resolve => setTimeout(resolve, 22_250));
state = await fetch(`${origin}/api/state`, { headers: playerHeaders }).then(r => r.json());
if (state.state !== "ready" || state.nextPosition !== 2) throw new Error("Expired question did not finalize its saved draft and advance");

console.log("Smoke test passed: import, invitation, timed flow, autosave, timeout, resume, review, scoring, and restart.");
