/**
 * Timing-engine tests.
 *
 * These target the properties the quiz's fairness actually rests on: that the
 * clock cannot be reset, that a question is finalized exactly once, and that an
 * abandoned question costs the player that question and no more.
 *
 *   npm test
 *
 * Runs against a scratch database with a short question window.
 */
import { test, before, after } from 'node:test';
import assert from 'node:assert/strict';
import { rmSync } from 'node:fs';

process.env.DB_PATH = 'data/test-timing.db';
// A short window keeps the suite fast. The grace is scaled down with it,
// otherwise the production 2s allowance would swallow the whole test window
// and "late" submissions would never actually read as late.
process.env.QUESTION_DURATION_MS = '400';
process.env.SUBMIT_GRACE_MS = '100';

const { QUESTION_DURATION_MS, TOTAL_QUESTIONS } = await import('./config.ts');
const { db, get, run, all } = await import('./db.ts');
const quiz = await import('./quiz.ts');

const sleep = (ms: number) => new Promise((r) => setTimeout(r, ms));

function freshPlayer(email: string): number {
  run(
    `INSERT INTO players (email, display_name, is_test) VALUES (?, ?, 1)
     ON CONFLICT(email) DO NOTHING`,
    email,
    email,
  );
  const row = get(`SELECT id FROM players WHERE email = ?`, email)!;
  const id = Number(row['id']);
  run(`DELETE FROM answers WHERE player_id = ?`, id);
  run(`UPDATE players SET started_at = NULL, finished_at = NULL WHERE id = ?`, id);
  return id;
}

before(() => {
  const database = db();
  database.exec('DELETE FROM answers');
  database.exec('DELETE FROM questions');
  for (let i = 1; i <= TOTAL_QUESTIONS; i += 1) {
    run(
      `INSERT INTO questions (position, prompt, canonical_answer) VALUES (?, ?, ?)`,
      i,
      `Question ${i}?`,
      `answer${i}`,
    );
  }
});

after(() => {
  for (const suffix of ['', '-wal', '-shm']) {
    try {
      rmSync(`data/test-timing.db${suffix}`);
    } catch {
      /* already gone */
    }
  }
});

test('a player must start before a question is served', () => {
  const id = freshPlayer('nostart@test');
  assert.equal(quiz.currentState(id).status, 'not_started');
});

test('reloading returns the same question with less time, not a fresh window', async () => {
  const id = freshPlayer('reload@test');
  quiz.startPlayer(id);

  const first = quiz.currentState(id);
  assert.equal(first.status, 'question');
  assert.equal(first.position, 1);

  await sleep(150);

  const second = quiz.currentState(id);
  assert.equal(second.status, 'question');
  assert.equal(second.position, 1, 'refresh must not advance the question');
  assert.ok(
    second.remainingMs < first.remainingMs - 100,
    `refresh must not reset the clock (was ${first.remainingMs}, now ${second.remainingMs})`,
  );
});

test('submitting advances to the next question', () => {
  const id = freshPlayer('advance@test');
  quiz.startPlayer(id);
  quiz.currentState(id);

  const outcome = quiz.submitAnswer(id, 1, 'my answer', false);
  assert.deepEqual(outcome, { result: 'recorded', expired: false });

  const next = quiz.currentState(id);
  assert.equal(next.status, 'question');
  assert.equal(next.position, 2);

  const stored = get(`SELECT * FROM answers WHERE player_id = ? AND position = 1`, id)!;
  assert.equal(stored['raw_answer'], 'my answer');
  assert.equal(stored['expired'], 0);
  assert.ok(Number(stored['elapsed_ms']) >= 0);
});

test('a question cannot be answered twice', () => {
  const id = freshPlayer('double@test');
  quiz.startPlayer(id);
  quiz.currentState(id);

  assert.equal(quiz.submitAnswer(id, 1, 'first', false).result, 'recorded');
  assert.equal(quiz.submitAnswer(id, 1, 'second', false).result, 'already_finalized');

  const stored = get(`SELECT raw_answer FROM answers WHERE player_id = ? AND position = 1`, id)!;
  assert.equal(stored['raw_answer'], 'first', 'the first submission stands');
});

test('a player cannot answer a question they were never served', () => {
  const id = freshPlayer('skip@test');
  quiz.startPlayer(id);
  quiz.currentState(id); // serves question 1 only

  assert.equal(quiz.submitAnswer(id, 7, 'cheating ahead', false).result, 'no_such_question');

  const state = quiz.currentState(id);
  assert.equal(state.status, 'question');
  assert.equal(state.status === 'question' ? state.position : null, 1, 'still on question 1');
});

test('an expired question is finalized with the last draft, not lost', async () => {
  const id = freshPlayer('draft@test');
  quiz.startPlayer(id);
  quiz.currentState(id);

  assert.equal(quiz.saveDraft(id, 1, 'half-typed guess'), true);
  await sleep(QUESTION_DURATION_MS + 80);

  // The player returns; the window has closed while they were away.
  const next = quiz.currentState(id);

  const stored = get(`SELECT * FROM answers WHERE player_id = ? AND position = 1`, id)!;
  assert.equal(stored['raw_answer'], 'half-typed guess', 'the draft becomes the answer');
  assert.equal(stored['expired'], 1);
  assert.ok(stored['submitted_at'], 'the row must be finalized');

  assert.equal(next.status, 'question');
  assert.equal(next.position, 2, 'abandoning costs exactly one question');
});

test('an expired question with no draft is finalized blank', async () => {
  const id = freshPlayer('blank@test');
  quiz.startPlayer(id);
  quiz.currentState(id);

  await sleep(QUESTION_DURATION_MS + 80);
  quiz.currentState(id);

  const stored = get(`SELECT * FROM answers WHERE player_id = ? AND position = 1`, id)!;
  assert.equal(stored['raw_answer'], '');
  assert.equal(stored['expired'], 1);
});

test('drafts are refused once the window has closed', async () => {
  const id = freshPlayer('latedraft@test');
  quiz.startPlayer(id);
  quiz.currentState(id);

  await sleep(QUESTION_DURATION_MS + 200);
  assert.equal(
    quiz.saveDraft(id, 1, 'typed after the buzzer'),
    false,
    'a post-deadline draft must not become the answer',
  );
});

test('a very late submission falls back to the pre-deadline draft', async () => {
  const id = freshPlayer('late@test');
  quiz.startPlayer(id);
  quiz.currentState(id);

  quiz.saveDraft(id, 1, 'what I had at the buzzer');
  await sleep(QUESTION_DURATION_MS + 200);

  // Simulates a browser whose timer was paused or whose clock was altered.
  const outcome = quiz.submitAnswer(id, 1, 'looked it up afterwards', false);
  assert.deepEqual(outcome, { result: 'recorded', expired: true });

  const stored = get(`SELECT raw_answer FROM answers WHERE player_id = ? AND position = 1`, id)!;
  assert.equal(stored['raw_answer'], 'what I had at the buzzer');
});

test('elapsed_ms is capped at the window, so a stalled client cannot skew the tiebreak', async () => {
  const id = freshPlayer('tiebreak@test');
  quiz.startPlayer(id);
  quiz.currentState(id);

  await sleep(QUESTION_DURATION_MS + 150);
  quiz.submitAnswer(id, 1, 'late', false);

  const stored = get(`SELECT elapsed_ms FROM answers WHERE player_id = ? AND position = 1`, id)!;
  assert.ok(
    Number(stored['elapsed_ms']) <= QUESTION_DURATION_MS,
    'elapsed time must never exceed the window',
  );
});

test('the quiz finishes after the last question and stays finished', () => {
  const id = freshPlayer('finish@test');
  quiz.startPlayer(id);

  for (let i = 1; i <= TOTAL_QUESTIONS; i += 1) {
    const state = quiz.currentState(id);
    assert.equal(state.status, 'question');
    assert.equal(state.position, i);
    quiz.submitAnswer(id, i, `answer ${i}`, false);
  }

  assert.equal(quiz.currentState(id).status, 'finished');
  assert.equal(quiz.currentState(id).status, 'finished', 'finished is stable across reloads');

  const finished = get(`SELECT finished_at FROM players WHERE id = ?`, id)!;
  assert.ok(finished['finished_at']);

  const count = get(
    `SELECT COUNT(*) AS n FROM answers WHERE player_id = ? AND submitted_at IS NOT NULL`,
    id,
  )!;
  assert.equal(Number(count['n']), TOTAL_QUESTIONS);
});

test('finalizeStaleSessions closes windows abandoned by players who never return', async () => {
  const id = freshPlayer('stale@test');
  quiz.startPlayer(id);
  quiz.currentState(id);

  await sleep(QUESTION_DURATION_MS + 80);

  const before = all(`SELECT * FROM answers WHERE player_id = ? AND submitted_at IS NULL`, id);
  assert.equal(before.length, 1, 'the abandoned question is still in flight');

  const closed = quiz.finalizeStaleSessions();
  assert.ok(closed >= 1);

  const after = all(`SELECT * FROM answers WHERE player_id = ? AND submitted_at IS NULL`, id);
  assert.equal(after.length, 0, 'nothing may remain unfinalized at scoring time');
});
