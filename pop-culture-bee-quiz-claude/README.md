# Pop Culture Bee — preliminary quiz (Claude's implementation)

Phase 1: a locally playable 50-question qualifying quiz.

Design document: [`../docs/POP-CULTURE-BEE-QUIZ.md`](../docs/POP-CULTURE-BEE-QUIZ.md)

> **This is one of two independent implementations.** Codex is building its own
> from [`../docs/POP-CULTURE-BEE-QUIZ-CODEX-BRIEF.md`](../docs/POP-CULTURE-BEE-QUIZ-CODEX-BRIEF.md).
> Nothing here should be merged into or shared with that work; the point is to
> compare two separate takes. This directory name is deliberately unambiguous so
> the two cannot collide.

## Running it

```bash
npm install
npm run seed     # 50 placeholder questions + a local test player
npm start        # http://127.0.0.1:8080
```

Other scripts:

```bash
npm test         # timing-engine tests
npm run dev      # auto-restart on file change
npm run typecheck
```

`POST /dev/reset` clears the local player's progress so the quiz can be replayed.
It is disabled when `NODE_ENV=production`.

## Stack

Node 24 + TypeScript + SQLite + Fastify.

- **TypeScript runs directly.** Node 24 strips types natively, so there is no
  build step and no bundler.
- **SQLite via `node:sqlite`**, built into Node, so there is no native
  compilation and the local and deployed databases are identical. It is still
  flagged experimental — meaning its API may shift across Node majors, not that
  it is unreliable — so every SQLite call goes through `src/db.ts` and swapping
  in `better-sqlite3` would be a one-file change.
- **Two dependencies** (`fastify`, `@fastify/cookie`, `@fastify/formbody`).
  `@fastify/static` was removed after `npm audit` flagged four path-traversal
  advisories against it; this app serves exactly two known files, so they are
  served from named routes instead. `npm audit` reports zero vulnerabilities.

## How the timing works

The one rule everything else rests on: **the server decides what question a
player is on and how much time is left.**

An `answers` row is written the moment a question is served, and its `served_at`
is the authoritative start of the window. The client is sent `remainingMs`,
never a flat 20 seconds, so reloading returns the same question with the time
actually left. The row is finalized exactly once — by a submission, or by the
deadline passing — under an `UPDATE ... WHERE submitted_at IS NULL` guard, so a
page load racing an auto-submit cannot double-answer or skip.

Session state is derived from the `answers` table rather than duplicated onto
`players`, so there is no second, disagreeing record of which question is live.

Three ways a question can close:

| Path | `auto_submitted` | `expired` | Recorded answer |
|---|---|---|---|
| Player clicks Submit | 0 | 0 | what they typed |
| Client timer fires at 0 | 1 | 0 | the answer box contents |
| Window closed while away | 0 | 1 | last pre-deadline draft, else blank |

Drafts autosave every 2 seconds and are **refused after the deadline**, which is
what makes `draft_answer` safe to promote to the final answer on expiry. A
submission arriving more than 2 seconds late — a paused timer, an altered clock
— falls back to that pre-deadline draft rather than being accepted.

`elapsed_ms` is capped at the window so a stalled client cannot skew the
tiebreak.

Client JavaScript is enhancement only. With it disabled the Submit button still
works and the server still closes the window on time.

## Verified

`npm test` covers 12 properties, including: a reload returns the same question
with less time; a question cannot be answered twice; a player cannot answer a
question they were never served; an expired question is finalized with the last
draft rather than lost; post-deadline drafts are refused; a very late submission
falls back to the pre-deadline draft; and `finalizeStaleSessions()` closes
windows abandoned by players who never return.

Also exercised in a real browser: the client timer fired at exactly 20.021s
after serve, submitted the autosaved draft, and advanced — and three successive
page loads returned the same question with 7340ms → 4313ms → 1280ms remaining.

## Not built yet

Phase 1 only. Still to come: magic-link invitations and the email allowlist
(Phase 2 — the current build identifies the player with a signed cookie
pointing at the seeded test account); admin imports, dashboard, review queue and
results (Phase 3); grading (normalization, alias matching, near-miss routing);
invitations and deadline enforcement (Phase 4).

## Open assumptions

These were assumed to keep Phase 1 moving and are cheap to change:

- The 20-second clock **starts when the question renders**, not after a
  per-question "Ready" tap.
- Players **see progress** ("Question 12 of 50") and a live countdown.
- The 50 real questions do not exist yet; `src/seed.ts` loads obvious
  placeholders.

`START_DEADLINE` (ISO-8601) enforces the entry cutoff and is unset locally. A
player already in progress always plays to completion.

## Environment

| Variable | Default | Notes |
|---|---|---|
| `PORT` / `HOST` | `8080` / `127.0.0.1` | |
| `DB_PATH` | `data/quiz.db` | Must sit outside any served directory |
| `COOKIE_SECRET` | random per boot | **Set before deploying**, or sessions drop on restart |
| `START_DEADLINE` | unset | ISO-8601 UTC entry cutoff |
| `QUESTION_DURATION_MS` | `20000` | Shortened by the test suite only |
| `SUBMIT_GRACE_MS` | `2000` | Latency allowance on a submission |
