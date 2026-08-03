import crypto from "node:crypto";
import fs from "node:fs";
import path from "node:path";
import { DatabaseSync } from "node:sqlite";
import express, { type Request, type Response, type NextFunction } from "express";

loadEnv();

const PORT = Number(process.env.PORT ?? 3100);
const DB_PATH = path.resolve(process.env.DATABASE_PATH ?? "./data/quiz.sqlite");
const APP_ORIGIN = process.env.APP_ORIGIN ?? `http://localhost:${PORT}`;
const ADMIN_CREDENTIAL = requiredEnv("ADMIN_PASSWORD");
const SESSION_SECRET = requiredEnv("SESSION_SECRET");
const CLOSES_AT = Date.parse(process.env.CLOSES_AT ?? "2026-08-07T04:59:00.000Z");
const GRACE_MS = Number(process.env.TRANSPORT_GRACE_MS ?? 2000);
const QUESTION_MS = 20_000;

fs.mkdirSync(path.dirname(DB_PATH), { recursive: true });
const db = new DatabaseSync(DB_PATH);
db.exec("PRAGMA journal_mode=WAL; PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000;");
db.exec(`
CREATE TABLE IF NOT EXISTS players (
  id INTEGER PRIMARY KEY,
  email TEXT NOT NULL UNIQUE COLLATE NOCASE,
  display_name TEXT NOT NULL DEFAULT '',
  token_hash TEXT NOT NULL UNIQUE,
  is_test INTEGER NOT NULL DEFAULT 0,
  created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS questions (
  id INTEGER PRIMARY KEY,
  position INTEGER NOT NULL UNIQUE CHECK(position BETWEEN 1 AND 50),
  prompt TEXT NOT NULL,
  canonical_answer TEXT NOT NULL,
  aliases_json TEXT NOT NULL DEFAULT '[]',
  included_in_score INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE IF NOT EXISTS attempts (
  id INTEGER PRIMARY KEY,
  player_id INTEGER NOT NULL REFERENCES players(id),
  generation INTEGER NOT NULL,
  status TEXT NOT NULL CHECK(status IN ('in_progress','completed','superseded','disqualified')),
  started_at TEXT NOT NULL,
  completed_at TEXT,
  superseded_at TEXT,
  restart_reason TEXT,
  UNIQUE(player_id, generation)
);
CREATE UNIQUE INDEX IF NOT EXISTS one_current_attempt
  ON attempts(player_id) WHERE status IN ('in_progress','completed');
CREATE TABLE IF NOT EXISTS exposures (
  id INTEGER PRIMARY KEY,
  attempt_id INTEGER NOT NULL REFERENCES attempts(id),
  question_id INTEGER NOT NULL REFERENCES questions(id),
  nonce TEXT NOT NULL UNIQUE,
  served_at TEXT NOT NULL,
  deadline_at TEXT NOT NULL,
  displayed_at TEXT,
  draft_text TEXT NOT NULL DEFAULT '',
  draft_sequence INTEGER NOT NULL DEFAULT 0,
  submitted_text TEXT,
  submitted_at TEXT,
  finalized_reason TEXT CHECK(finalized_reason IN ('manual','timeout')),
  normalized_answer TEXT,
  verdict TEXT CHECK(verdict IN ('correct','incorrect','unresolved')),
  UNIQUE(attempt_id, question_id)
);
CREATE TABLE IF NOT EXISTS grading_rules (
  id INTEGER PRIMARY KEY,
  question_id INTEGER NOT NULL REFERENCES questions(id),
  normalized_answer TEXT NOT NULL,
  verdict TEXT NOT NULL CHECK(verdict IN ('correct','incorrect')),
  note TEXT NOT NULL DEFAULT '',
  reviewed_at TEXT NOT NULL,
  UNIQUE(question_id, normalized_answer)
);
CREATE TABLE IF NOT EXISTS audit_events (
  id INTEGER PRIMARY KEY,
  attempt_id INTEGER REFERENCES attempts(id),
  kind TEXT NOT NULL,
  detail_json TEXT NOT NULL DEFAULT '{}',
  created_at TEXT NOT NULL
);
`);

const app = express();
app.disable("x-powered-by");
app.use(express.urlencoded({ extended: false, limit: "1mb" }));
app.use(express.json({ limit: "64kb" }));
app.use((_req, res, next) => {
  res.setHeader("X-Frame-Options", "DENY");
  res.setHeader("X-Content-Type-Options", "nosniff");
  res.setHeader("Referrer-Policy", "no-referrer");
  res.setHeader("Cache-Control", "no-store");
  next();
});

type Player = { id: number; email: string; display_name: string; is_test: number };
type Attempt = { id: number; player_id: number; generation: number; status: string; started_at: string };
type Question = { id: number; position: number; prompt: string; canonical_answer: string; aliases_json: string; included_in_score: number };
type Exposure = { id: number; attempt_id: number; question_id: number; nonce: string; served_at: string; deadline_at: string; displayed_at: string | null; draft_text: string; draft_sequence: number; submitted_text: string | null; finalized_reason: string | null };

function loadEnv() {
  const filename = path.resolve(".env");
  if (!fs.existsSync(filename)) return;
  for (const line of fs.readFileSync(filename, "utf8").split(/\r?\n/)) {
    const match = line.match(/^([A-Z0-9_]+)=(.*)$/);
    if (match && process.env[match[1]!] === undefined) process.env[match[1]!] = match[2]!;
  }
}

function requiredEnv(name: string) {
  const value = process.env[name];
  if (!value || value.length < 16) throw new Error(`${name} must be configured with at least 16 characters.`);
  return value;
}

function nowIso() { return new Date().toISOString(); }
function sha(value: string) { return crypto.createHash("sha256").update(value).digest("hex"); }
function normalize(value: string) {
  return value.normalize("NFKC").trim().toLocaleLowerCase("en-US").replace(/\s+/g, " ");
}
function randomToken(bytes = 32) { return crypto.randomBytes(bytes).toString("base64url"); }
function sign(value: string) { return `${value}.${crypto.createHmac("sha256", SESSION_SECRET).update(value).digest("base64url")}`; }
function unsign(value?: string) {
  if (!value) return null;
  const dot = value.lastIndexOf(".");
  if (dot < 1) return null;
  const raw = value.slice(0, dot);
  const expected = sign(raw);
  if (expected.length !== value.length || !crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(value))) return null;
  return raw;
}
function cookies(req: Request) {
  return Object.fromEntries((req.headers.cookie ?? "").split(/;\s*/).filter(Boolean).map(part => {
    const i = part.indexOf("=");
    return [decodeURIComponent(part.slice(0, i)), decodeURIComponent(part.slice(i + 1))];
  }));
}
function setCookie(res: Response, name: string, value: string, maxAgeSeconds = 604800) {
  res.cookie(name, value, { httpOnly: true, secure: APP_ORIGIN.startsWith("https:"), sameSite: "lax", maxAge: maxAgeSeconds * 1000, path: "/" });
}
function currentPlayer(req: Request): Player | null {
  const id = Number(unsign(cookies(req).pcb_player));
  if (!Number.isSafeInteger(id)) return null;
  return (db.prepare("SELECT id,email,display_name,is_test FROM players WHERE id=?").get(id) as Player | undefined) ?? null;
}
function currentAttempt(playerId: number): Attempt | null {
  return (db.prepare("SELECT * FROM attempts WHERE player_id=? AND status IN ('in_progress','completed') ORDER BY generation DESC LIMIT 1").get(playerId) as Attempt | undefined) ?? null;
}
function activeExposure(attemptId: number): (Exposure & { position: number; prompt: string }) | null {
  return (db.prepare(`SELECT e.*,q.position,q.prompt FROM exposures e JOIN questions q ON q.id=e.question_id
    WHERE e.attempt_id=? AND e.submitted_at IS NULL ORDER BY q.position DESC LIMIT 1`).get(attemptId) as (Exposure & { position: number; prompt: string }) | undefined) ?? null;
}
function questionCount() { return Number((db.prepare("SELECT COUNT(*) AS n FROM questions").get() as { n: number }).n); }
function finalizedCount(attemptId: number) { return Number((db.prepare("SELECT COUNT(*) AS n FROM exposures WHERE attempt_id=? AND submitted_at IS NOT NULL").get(attemptId) as { n: number }).n); }
function autoVerdict(question: Question, answer: string): "correct" | "incorrect" | "unresolved" {
  const key = normalize(answer);
  if (!key) return "incorrect";
  const accepted = [question.canonical_answer, ...(JSON.parse(question.aliases_json) as string[])].map(normalize);
  return accepted.includes(key) ? "correct" : "unresolved";
}
function finalize(exposure: Exposure, reason: "manual" | "timeout", text: string) {
  const q = db.prepare("SELECT * FROM questions WHERE id=?").get(exposure.question_id) as Question;
  const answer = text.slice(0, 500);
  const verdict = autoVerdict(q, answer);
  const changed = db.prepare(`UPDATE exposures SET submitted_text=?,submitted_at=?,finalized_reason=?,normalized_answer=?,verdict=?
    WHERE id=? AND submitted_at IS NULL`).run(answer, nowIso(), reason, normalize(answer), verdict, exposure.id).changes;
  if (changed) db.prepare("INSERT INTO audit_events(attempt_id,kind,detail_json,created_at) VALUES(?,?,?,?)")
    .run(exposure.attempt_id, "answer_finalized", JSON.stringify({ questionId: exposure.question_id, reason }), nowIso());
  const total = finalizedCount(exposure.attempt_id);
  if (total >= 50) db.prepare("UPDATE attempts SET status='completed',completed_at=? WHERE id=? AND status='in_progress'").run(nowIso(), exposure.attempt_id);
}
function expireIfNeeded(attemptId: number) {
  const exposure = activeExposure(attemptId);
  if (exposure && Date.now() > Date.parse(exposure.deadline_at) + GRACE_MS) finalize(exposure, "timeout", exposure.draft_text);
}
function requirePlayer(req: Request, res: Response, next: NextFunction) {
  const player = currentPlayer(req);
  if (!player) return res.status(401).json({ error: "Invitation session required." });
  res.locals.player = player;
  next();
}
function isAdmin(req: Request) { return unsign(cookies(req).pcb_admin) === "admin"; }
function requireAdmin(req: Request, res: Response, next: NextFunction) {
  if (!isAdmin(req)) return res.redirect("/admin");
  next();
}

app.get("/health", (_req, res) => res.json({ ok: true, database: db.prepare("SELECT 1 AS ok").get(), release: process.env.RELEASE_ID ?? "local" }));
app.get("/", (_req, res) => res.send(page("Pop Culture Bee", `<main class="card"><p class="eyebrow">Trivia Nationals</p><h1>Pop Culture Bee Preliminary Quiz</h1><p>This quiz is available only through a personalized invitation link.</p></main>`)));

app.get("/invite/:token", (req, res) => {
  const player = db.prepare("SELECT id FROM players WHERE token_hash=?").get(sha(req.params.token)) as { id: number } | undefined;
  if (!player) return res.status(404).send(page("Invalid invitation", `<main class="card"><h1>That invitation link is not valid.</h1><p>Please contact Trivia Nationals for help.</p></main>`));
  setCookie(res, "pcb_player", sign(String(player.id)));
  res.redirect("/quiz");
});

app.get("/quiz", (req, res) => {
  if (!currentPlayer(req)) return res.redirect("/");
  res.send(playerPage());
});

app.get("/api/state", requirePlayer, (req, res) => {
  const player = res.locals.player as Player;
  const attempt = currentAttempt(player.id);
  if (attempt?.status === "in_progress") expireIfNeeded(attempt.id);
  const refreshed = currentAttempt(player.id);
  if (!refreshed) return res.json({ state: "prestart", player: player.display_name || player.email, questionCount: questionCount(), closesAt: new Date(CLOSES_AT).toISOString() });
  if (refreshed.status === "completed") return res.json({ state: "complete" });
  const exposure = activeExposure(refreshed.id);
  if (exposure) return res.json({ state: "question", position: exposure.position, prompt: exposure.prompt, nonce: exposure.nonce,
    deadlineAt: exposure.deadline_at, firstDisplay: exposure.displayed_at === null, draft: exposure.draft_text, graceMs: GRACE_MS });
  return res.json({ state: "ready", nextPosition: finalizedCount(refreshed.id) + 1 });
});

app.post("/api/ready", requirePlayer, (req, res) => {
  const player = res.locals.player as Player;
  if (questionCount() !== 50) return res.status(503).json({ error: "The quiz is not ready yet." });
  let attempt = currentAttempt(player.id);
  if (!attempt) {
    if (Date.now() > CLOSES_AT) return res.status(403).json({ error: "The start deadline has passed." });
    const generation = Number((db.prepare("SELECT COALESCE(MAX(generation),0)+1 AS n FROM attempts WHERE player_id=?").get(player.id) as { n: number }).n);
    const result = db.prepare("INSERT INTO attempts(player_id,generation,status,started_at) VALUES(?,?,'in_progress',?)").run(player.id, generation, nowIso());
    attempt = db.prepare("SELECT * FROM attempts WHERE id=?").get(result.lastInsertRowid) as Attempt;
  }
  if (attempt.status === "completed") return res.json({ ok: true });
  expireIfNeeded(attempt.id);
  const existing = activeExposure(attempt.id);
  if (existing) return res.json({ ok: true });
  const position = finalizedCount(attempt.id) + 1;
  if (position > 50) return res.json({ ok: true });
  const question = db.prepare("SELECT id FROM questions WHERE position=?").get(position) as { id: number };
  const served = Date.now();
  db.prepare("INSERT INTO exposures(attempt_id,question_id,nonce,served_at,deadline_at) VALUES(?,?,?,?,?)")
    .run(attempt.id, question.id, randomToken(18), new Date(served).toISOString(), new Date(served + QUESTION_MS).toISOString());
  res.json({ ok: true });
});

app.post("/api/displayed", requirePlayer, (req, res) => {
  const attempt = currentAttempt((res.locals.player as Player).id);
  if (attempt) db.prepare("UPDATE exposures SET displayed_at=COALESCE(displayed_at,?) WHERE attempt_id=? AND nonce=?").run(nowIso(), attempt.id, String(req.body.nonce));
  res.json({ ok: true });
});

app.post("/api/draft", requirePlayer, (req, res) => {
  const attempt = currentAttempt((res.locals.player as Player).id);
  if (!attempt || attempt.status !== "in_progress") return res.status(409).json({ error: "No active attempt." });
  const exposure = activeExposure(attempt.id);
  if (!exposure || exposure.nonce !== req.body.nonce) return res.status(409).json({ error: "Question changed." });
  const sequence = Math.max(0, Number(req.body.sequence) || 0);
  if (Date.now() <= Date.parse(exposure.deadline_at) + GRACE_MS) {
    db.prepare("UPDATE exposures SET draft_text=?,draft_sequence=? WHERE id=? AND submitted_at IS NULL AND draft_sequence<?")
      .run(String(req.body.text ?? "").slice(0, 500), sequence, exposure.id, sequence);
  }
  res.json({ ok: true });
});

app.post("/api/submit", requirePlayer, (req, res) => {
  const attempt = currentAttempt((res.locals.player as Player).id);
  if (!attempt || attempt.status !== "in_progress") return res.status(409).json({ error: "No active attempt." });
  const exposure = activeExposure(attempt.id);
  if (!exposure || exposure.nonce !== req.body.nonce) return res.json({ ok: true });
  const timely = Date.now() <= Date.parse(exposure.deadline_at) + GRACE_MS;
  finalize(exposure, timely ? "manual" : "timeout", timely ? String(req.body.text ?? "") : exposure.draft_text);
  res.json({ ok: true });
});

app.get("/admin", (req, res) => {
  if (!isAdmin(req)) return res.send(page("Admin sign in", `<main class="card narrow"><p class="eyebrow">Quiz administration</p><h1>Sign in</h1><form method="post" action="/admin/login"><label>Password<input name="password" type="password" required autofocus></label><button>Sign in</button></form></main>`));
  res.send(adminPage());
});
app.post("/admin/login", (req, res) => {
  const supplied = Buffer.from(String(req.body.password ?? ""));
  const expected = Buffer.from(ADMIN_CREDENTIAL);
  if (supplied.length !== expected.length || !crypto.timingSafeEqual(supplied, expected)) return res.status(403).send(page("Sign in failed", `<main class="card"><h1>Sign in failed</h1><a href="/admin">Try again</a></main>`));
  setCookie(res, "pcb_admin", sign("admin"), 43200);
  res.redirect("/admin");
});
app.post("/admin/players", requireAdmin, (req, res) => {
  const lines = String(req.body.players ?? "").split(/\r?\n/).map(x => x.trim()).filter(Boolean);
  const links: string[] = [];
  const insert = db.prepare("INSERT OR IGNORE INTO players(email,display_name,token_hash,is_test,created_at) VALUES(?,?,?,?,?)");
  for (const line of lines) {
    const [emailRaw, name = "", test = ""] = line.split(",").map(x => x.trim());
    const email = (emailRaw ?? "").toLowerCase();
    if (!/^\S+@\S+\.\S+$/.test(email)) continue;
    const token = randomToken();
    const result = insert.run(email, name, sha(token), /^(1|yes|true|test)$/i.test(test) ? 1 : 0, nowIso());
    if (result.changes) links.push(`${email},${name},${APP_ORIGIN}/invite/${token}`);
  }
  res.send(page("Invitation links", `<main class="card wide"><h1>New invitation links</h1><p>Save these now; tokens are stored only as hashes.</p><textarea rows="18" readonly>${esc(links.join("\n"))}</textarea><p><a href="/admin">Return to admin</a></p></main>`));
});
app.post("/admin/questions", requireAdmin, (req, res) => {
  if (Number((db.prepare("SELECT COUNT(*) AS n FROM attempts").get() as { n: number }).n) > 0) return res.status(409).send(page("Import blocked", `<main class="card"><h1>Question import blocked</h1><p>An attempt already exists. The active bank is frozen.</p><a href="/admin">Return</a></main>`));
  const parsed = JSON.parse(String(req.body.questions ?? "[]")) as Array<{ position: number; prompt: string; answer: string; aliases?: string[] }>;
  if (parsed.length !== 50 || new Set(parsed.map(q => q.position)).size !== 50 || parsed.some(q => q.position < 1 || q.position > 50 || !q.prompt?.trim() || !q.answer?.trim())) {
    return res.status(400).send(page("Invalid questions", `<main class="card"><h1>Import rejected</h1><p>Provide exactly 50 unique positions from 1 through 50, each with prompt and answer.</p><a href="/admin">Return</a></main>`));
  }
  db.exec("BEGIN");
  try {
    db.exec("DELETE FROM questions");
    const insert = db.prepare("INSERT INTO questions(position,prompt,canonical_answer,aliases_json) VALUES(?,?,?,?)");
    for (const q of parsed.sort((a, b) => a.position - b.position)) insert.run(q.position, q.prompt.trim(), q.answer.trim(), JSON.stringify(q.aliases ?? []));
    db.exec("COMMIT");
  } catch (error) { db.exec("ROLLBACK"); throw error; }
  res.redirect("/admin");
});
app.post("/admin/review", requireAdmin, (req, res) => {
  const questionId = Number(req.body.questionId);
  const key = String(req.body.answer ?? "");
  const verdict = req.body.verdict === "correct" ? "correct" : "incorrect";
  db.prepare(`INSERT INTO grading_rules(question_id,normalized_answer,verdict,note,reviewed_at) VALUES(?,?,?,?,?)
    ON CONFLICT(question_id,normalized_answer) DO UPDATE SET verdict=excluded.verdict,note=excluded.note,reviewed_at=excluded.reviewed_at`)
    .run(questionId, key, verdict, String(req.body.note ?? "").slice(0, 500), nowIso());
  db.prepare("UPDATE exposures SET verdict=? WHERE question_id=? AND normalized_answer=?").run(verdict, questionId, key);
  res.redirect("/admin#review");
});
app.post("/admin/restart", requireAdmin, (req, res) => {
  const playerId = Number(req.body.playerId);
  const reason = String(req.body.reason ?? "Technical failure").slice(0, 500);
  db.prepare("UPDATE attempts SET status='superseded',superseded_at=?,restart_reason=? WHERE player_id=? AND status IN ('in_progress','completed')").run(nowIso(), reason, playerId);
  res.redirect("/admin");
});
app.get("/admin/results.csv", requireAdmin, (_req, res) => {
  const rows = results().filter(row => !row.is_test);
  res.type("text/csv").attachment("pop-culture-bee-results.csv").send("rank,email,name,score,status,test\n" + rows.map((r, i) => `${i + 1},${csv(r.email)},${csv(r.display_name)},${r.score},${r.status},${r.is_test}`).join("\n"));
});

app.use((error: unknown, _req: Request, res: Response, _next: NextFunction) => {
  console.error(error);
  res.status(500).send(page("Unexpected error", `<main class="card"><h1>Something went wrong.</h1><p>Your saved progress has not been intentionally cleared. Please try again.</p></main>`));
});

function results() {
  return db.prepare(`SELECT p.id,p.email,p.display_name,p.is_test,a.status,
    COALESCE(SUM(CASE WHEN q.included_in_score=1 AND e.verdict='correct' THEN 1 ELSE 0 END),0) AS score
    FROM players p LEFT JOIN attempts a ON a.player_id=p.id AND a.status IN ('in_progress','completed')
    LEFT JOIN exposures e ON e.attempt_id=a.id LEFT JOIN questions q ON q.id=e.question_id
    GROUP BY p.id,a.id ORDER BY score DESC,p.email ASC`).all() as Array<{ id: number; email: string; display_name: string; is_test: number; status: string | null; score: number }>;
}

function adminPage() {
  const players = results();
  const unresolved = db.prepare(`SELECT q.id AS question_id,q.position,e.normalized_answer,COUNT(*) AS n
    FROM exposures e JOIN questions q ON q.id=e.question_id WHERE e.verdict='unresolved'
    GROUP BY q.id,q.position,e.normalized_answer ORDER BY q.position,n DESC`).all() as Array<{ question_id: number; position: number; normalized_answer: string; n: number }>;
  return page("Quiz admin", `<main class="admin"><header><p class="eyebrow">Trivia Nationals</p><h1>Pop Culture Bee Quiz</h1><p>${questionCount()}/50 questions · cutoff ${new Date(CLOSES_AT).toLocaleString("en-US", { timeZone: "America/Chicago", timeZoneName: "short" })} · release ${esc(process.env.RELEASE_ID ?? "local")}</p></header>
  <section class="panel"><h2>Import questions</h2><p>JSON array: <code>{"position":1,"prompt":"…","answer":"…","aliases":["…"]}</code>. Import locks after the first attempt.</p><form method="post" action="/admin/questions"><textarea name="questions" rows="7" required></textarea><button>Validate and import 50 questions</button></form></section>
  <section class="panel"><h2>Import players</h2><p>One per line: <code>email,name,test</code>. New personalized links appear once and must be saved.</p><form method="post" action="/admin/players"><textarea name="players" rows="7" required></textarea><button>Create invitation links</button></form></section>
  <section class="panel"><h2>Progress and results</h2><p><a class="button" href="/admin/results.csv">Download results CSV</a> Test accounts are excluded from the CSV.</p><div class="table"><table><thead><tr><th>Email</th><th>Name</th><th>Status</th><th>Score</th><th>Test</th><th>Restart</th></tr></thead><tbody>${players.map(p => `<tr><td>${esc(p.email)}</td><td>${esc(p.display_name)}</td><td>${esc(p.status ?? "not started")}</td><td>${p.score}</td><td>${p.is_test ? "yes" : ""}</td><td><form method="post" action="/admin/restart"><input type="hidden" name="playerId" value="${p.id}"><input name="reason" aria-label="Restart reason" placeholder="Reason" required><button class="small">Grant restart</button></form></td></tr>`).join("")}</tbody></table></div></section>
  <section class="panel" id="review"><h2>Review queue</h2>${unresolved.length ? unresolved.map(v => `<form class="review" method="post" action="/admin/review"><input type="hidden" name="questionId" value="${v.question_id}"><input type="hidden" name="answer" value="${esc(v.normalized_answer)}"><strong>Q${v.position}</strong><span>“${esc(v.normalized_answer)}”</span><span>${v.n} player${v.n === 1 ? "" : "s"}</span><input name="note" placeholder="Optional note"><button name="verdict" value="correct">Correct</button><button class="secondary" name="verdict" value="incorrect">Incorrect</button></form>`).join("") : "<p>No unresolved answers yet.</p>"}</section></main>`);
}

function playerPage() {
  return page("Pop Culture Bee Quiz", `<main id="app" class="card quiz" aria-live="polite"><p>Loading your quiz…</p></main><script>
const root=document.querySelector('#app');let state=null,seq=0,timer=null,saving=null;
const escapeHtml=s=>String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(url,body){const r=await fetch(url,{method:body?'POST':'GET',headers:body?{'content-type':'application/json'}:{},body:body?JSON.stringify(body):undefined});const data=await r.json();if(!r.ok)throw new Error(data.error||'Request failed');return data}
async function load(){clearInterval(timer);try{state=await api('/api/state');render()}catch(e){root.innerHTML='<h1>Unable to load</h1><p>'+escapeHtml(e.message)+'</p><button onclick="load()">Try again</button>'}}
function render(){if(state.state==='prestart'){root.innerHTML='<p class="eyebrow">Trivia Nationals</p><h1>Pop Culture Bee Preliminary</h1><p>Welcome, '+escapeHtml(state.player)+'. You will answer 50 text questions, one at a time. Each question has 20 seconds.</p><div class="notice"><strong>If you leave:</strong> the current question expires using the most recent saved draft (blank if none). When you return, you continue with the next question, so walking away costs exactly one question.</div><p>You will not see correctness or a score. Your result determines whether you advance to the LIVE game Saturday.</p><button id="start">I’m ready to begin</button>';document.querySelector('#start').onclick=ready}
else if(state.state==='ready'){root.innerHTML='<p class="eyebrow">Question '+state.nextPosition+' of 50</p><h1>Ready?</h1><p>Your 20 seconds begin when the question appears.</p><button id="ready">Show question</button>';document.querySelector('#ready').onclick=ready}
else if(state.state==='complete'){root.innerHTML='<p class="eyebrow">Finished</p><h1>Thank you!</h1><p>Your score determines whether you advance to the LIVE Pop Culture Bee game on Saturday. Trivia Nationals will announce results separately.</p>'}
else showQuestion()}
async function ready(){const b=document.querySelector('button');b.disabled=true;try{await api('/api/ready',{});await load()}catch(e){alert(e.message);b.disabled=false}}
function showQuestion(){seq=0;state.visibleDeadline=state.firstDisplay?Date.now()+20000:new Date(state.deadlineAt).getTime();root.innerHTML='<div class="quizhead"><span>Question '+state.position+' of 50</span><strong id="clock">20</strong></div><h1 class="prompt">'+escapeHtml(state.prompt)+'</h1><form id="answerForm"><label for="answer">Your answer</label><input id="answer" maxlength="500" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" value="'+escapeHtml(state.draft||'')+'"><button>Submit answer</button></form><p id="save" class="muted">Your draft saves automatically.</p>';
const input=document.querySelector('#answer');input.focus();api('/api/displayed',{nonce:state.nonce}).catch(()=>{});input.addEventListener('input',()=>{clearTimeout(saving);saving=setTimeout(saveDraft,350)});document.querySelector('#answerForm').onsubmit=e=>{e.preventDefault();submit(false)};tick();timer=setInterval(tick,100)}
function tick(){const left=Math.max(0,state.visibleDeadline-Date.now());document.querySelector('#clock').textContent=(left/1000).toFixed(1);if(left<=0){clearInterval(timer);submit(true)}}
async function saveDraft(){seq++;const input=document.querySelector('#answer');if(!input)return;try{await api('/api/draft',{nonce:state.nonce,sequence:seq,text:input.value});const s=document.querySelector('#save');if(s)s.textContent='Draft saved.'}catch{}}
async function submit(auto){clearInterval(timer);clearTimeout(saving);const input=document.querySelector('#answer');const text=input?input.value:'';root.querySelectorAll('button,input').forEach(x=>x.disabled=true);try{await api('/api/submit',{nonce:state.nonce,text,auto});await load()}catch(e){root.querySelectorAll('button,input').forEach(x=>x.disabled=false);alert(e.message)}}
load();
</script>`);
}

function esc(value: unknown) { return String(value ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]!); }
function csv(value: unknown) { return `"${String(value ?? "").replaceAll('"', '""')}"`; }
function page(title: string, body: string) {
  return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>${esc(title)}</title><style>
:root{color-scheme:dark;--ink:#fff8df;--muted:#c7bda4;--gold:#ffc928;--orange:#ff6b35;--panel:#15142a;--line:#393653;font-family:Inter,ui-sans-serif,system-ui,sans-serif}*{box-sizing:border-box}body{margin:0;min-height:100vh;color:var(--ink);background:radial-gradient(circle at 20% 0,#352060 0,transparent 38%),linear-gradient(145deg,#0c0b18,#171126);padding:24px}body:before{content:"";position:fixed;inset:0;pointer-events:none;opacity:.16;background-image:radial-gradient(#fff 1px,transparent 1px);background-size:28px 28px}.card,.admin{position:relative;max-width:760px;margin:7vh auto;background:rgba(21,20,42,.96);border:1px solid var(--line);border-radius:24px;padding:clamp(24px,5vw,48px);box-shadow:0 26px 70px #0008}.admin{max-width:1180px;margin:24px auto}.narrow{max-width:480px}.wide{max-width:900px}.eyebrow{text-transform:uppercase;letter-spacing:.16em;color:var(--gold);font-weight:800;font-size:.78rem}h1{font-size:clamp(2rem,6vw,4.2rem);line-height:.98;margin:.3em 0}h2{font-size:1.5rem}p{line-height:1.6;color:var(--muted)}label{display:block;font-weight:700;margin:18px 0 8px}input,textarea{width:100%;border:1px solid #4e496b;border-radius:12px;background:#0e0d1c;color:var(--ink);padding:14px;font:inherit}input:focus,textarea:focus{outline:3px solid #ffc92855;border-color:var(--gold)}button,.button{display:inline-block;border:0;border-radius:999px;background:linear-gradient(90deg,var(--gold),var(--orange));color:#151018;font-weight:900;padding:14px 22px;font:inherit;cursor:pointer;text-decoration:none;margin-top:12px}button:disabled{opacity:.55}.secondary{background:#36324d;color:var(--ink)}.small{padding:8px 12px;font-size:.8rem}.notice{border-left:4px solid var(--gold);background:#211e39;padding:16px;line-height:1.5}.quizhead{display:flex;justify-content:space-between;color:var(--gold);font-weight:800}.quizhead strong{font-variant-numeric:tabular-nums;font-size:1.4rem}.prompt{font-size:clamp(1.65rem,5vw,3.2rem);line-height:1.1;margin:1em 0}.quiz input{font-size:1.3rem}.muted{font-size:.85rem}.panel{border-top:1px solid var(--line);padding:24px 0}.table{overflow:auto}table{width:100%;border-collapse:collapse}th,td{text-align:left;padding:10px;border-bottom:1px solid var(--line);vertical-align:top}.review{display:grid;grid-template-columns:60px 1fr 100px 1fr auto auto;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--line)}code{color:var(--gold)}@media(max-width:750px){body{padding:12px}.card{margin:3vh auto;border-radius:18px}.review{grid-template-columns:1fr}.admin{padding:20px}h1{font-size:2.25rem}}
</style></head><body>${body}</body></html>`;
}

const server = app.listen(PORT, () => console.log(`Pop Culture Bee Codex app: ${APP_ORIGIN}`));
server.ref();
