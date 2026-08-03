# Pop Culture Bee preliminary quiz — independent Codex design

Status: **design recommendation only; no implementation begun.**

This is an independent response to `POP-CULTURE-BEE-QUIZ-CODEX-BRIEF.md`.
It intentionally does not replace or revise Claude's proposal in
`POP-CULTURE-BEE-QUIZ.md`.

## Executive recommendation

Build a small server-rendered **Node.js/TypeScript application backed by
PostgreSQL**, deployed as one always-on web service in a US Central or nearby
region. Use a migration-based database schema, a container image identified by
its Git commit, and a provider-managed database with point-in-time or daily
backups. Keep the browser JavaScript deliberately small.

My preference is PostgreSQL rather than production SQLite. SQLite can serve a
few hundred players easily, but its operational simplicity is overstated once
the database lives on a single PaaS volume: the app becomes tied to one
instance, deploy/volume attachment behavior matters, backups need custom
handling, and a concurrent review queue introduces write contention at exactly
the time the cut list is being prepared. Managed Postgres removes those event-
week concerns and makes an accidental second application instance safe. SQLite
remains ideal for local automated tests, but local development should use the
same Postgres schema in a container before release.

Do not select a vendor by habit. Run a two-hour deployment spike against one
candidate and require all of these before committing to it:

- always-on service with no cold starts;
- managed TLS and custom-domain support;
- a US region with measured warm API latency;
- managed Postgres backups and a documented restore path;
- atomic/image-based deploys, immutable release identifiers, and rollback;
- straightforward log access and health checks.

Render is the first candidate I would test because the operating model is
simple. Fly.io is a reasonable fallback but exposes more infrastructure knobs
than this application benefits from. HostGator is the fallback only if a new
vendor cannot be provisioned promptly. If HostGator wins, use PHP 8 plus
MariaDB, not Node or SQLite, and prove an atomic version-directory deployment
with server-side rename/symlink before building against it. Bare multi-file
FTPS is a release blocker after the July 28 truncation incident.

This Render fit was checked against its official documentation on 2026-07-31:
paid Postgres has point-in-time recovery and exportable logical backups;
web services support custom domains, health checks, zero-downtime deploys, and
instant rollback; and the deploy history identifies the live commit. The free
web tier sleeps after inactivity and is therefore explicitly unsuitable here.
See [Postgres backups](https://render.com/docs/postgresql-backups),
[web services](https://render.com/docs/web-services), and
[deploy behavior](https://render.com/docs/deploys).

This agrees with Claude on TypeScript and a PaaS, but disagrees on production
SQLite and on leaving Fly.io and Render as interchangeable late choices.

## Scope and explicit assumptions

Until the owner answers the open questions, this design assumes:

- no more than 500 invited players and at most 100 simultaneously active;
- one primary grader, with safe concurrent use by a second grader;
- questions arrive as a spreadsheet and are imported from CSV;
- every question has a visible countdown and `Question N of 50` progress;
- there is a Ready action before Q1 and between questions; the 20-second clock
  begins when the question becomes visible after that action;
- the application does not send invitations or reminders in its first release;
  it exports personalized invitation rows for an external mail merge;
- the prelim produces an ordered/categorized decision list only; it does not
  seed a Saturday bracket;
- staff test accounts exist and are excluded from official results.

The exact closing date and year, time-zone identifier (`America/Chicago`), N,
and tie policy must be configuration values confirmed before production data
is loaded. "Thursday" alone must never appear in machine configuration.

## Player identity and attempt lifecycle

An invitation contains a high-entropy, single-player secret. Store only its
hash. Redeeming it creates an HTTP-only, Secure, SameSite session cookie so the
secret does not remain in every URL or application log. Rate-limit token and
admin-login failures. A forwarded invitation remains usable by its recipient;
the fixed requirement establishes email allowlisting, not strong proof that
the person typing is the mailbox owner.

Model **attempts explicitly**, rather than storing all mutable quiz state on a
player row:

- a player can have many attempt records over the application's lifetime but
  only one official attempt generation at a time;
- an admin restart creates a new generation and marks the old one superseded;
  it never deletes or overwrites the old answers;
- each restart records administrator, timestamp, and reason;
- official rankings include only the current non-test generation;
- question-bank version and application release are pinned on the attempt.

This makes the one-attempt rule, test exclusion, restarts, and later disputes
auditable. A restart should normally restart at Q1; because it reveals already
seen questions, it is an exceptional staff decision, not a routine self-
service feature.

Attempt states should be `not_started`, `in_progress`, `completed`,
`superseded`, and `disqualified`. Avoid a catch-all player status that mixes
identity, invitation, and gameplay state.

## Timing protocol

### Where Claude is right

The deadline must survive refreshes and must be judged by the server. The next
question must never be embedded in HTML, prefetched into browser state, or
returned until the previous answer has been finalized. Draft autosave is worth
the modest traffic: 500 players x 50 questions x roughly 10 saves is only
about 250,000 small writes over several days.

Every mutation is transactional and idempotent. A final submit, timeout, retry,
double tap, and resume race must be able to produce only one finalized answer
and at most one next question.

### Where the proposal is incomplete

Starting the server clock before the response reaches the phone makes a slow
request consume typing time. That is not merely a cosmetic cold-start problem;
players on slower mobile networks receive less than 20 visible seconds. The
server also cannot know the exact instant a browser rendered a question.

Use this bounded hybrid protocol:

1. The Ready screen contains no future prompt. A click requests the question.
2. In one transaction, the server creates the question exposure with a unique
   nonce and a server start/deadline, then returns the prompt and server time.
3. The browser starts a visible 20.0-second countdown on receipt using a
   monotonic clock and immediately sends a best-effort `displayed` event.
4. Drafts save on input after a short debounce and at least every two seconds.
   Each carries the exposure nonce and sequence number; the server keeps the
   newest draft received within its acceptance window.
5. At visible zero, the browser submits immediately. Manual and automatic
   finalization use the same endpoint.
6. The server accepts a final packet through a small, fixed transport allowance
   after its nominal deadline (provisionally two seconds). It never gives that
   allowance to the displayed countdown. Anything later finalizes from the last
   timely draft.
7. Any later request first finalizes an expired exposure from its last timely
   draft, then shows the Ready screen for the next unserved question. No
   background scheduler is required for correctness.

The transport allowance is not perfect anti-cheat, but it bounds the advantage
while preventing normal round-trip time from deleting an answer submitted at
visible zero. Measure real phones during rehearsal and ratify the allowance;
do not hide it as an implementation constant.

The abandonment rule then follows naturally: an expired exposure is finalized
once, and the next prompt is withheld until the player returns and presses
Ready. State this consequence on the pre-start screen.

The server start, displayed event, draft receipts, and final receipt should be
retained for diagnosis. Do not advertise millisecond precision that the system
cannot actually provide.

### Anti-cheat limits

The defensible controls are one secret per player, fixed attempt state,
server-bounded exposure, no future-question preloading, no feedback, and an
auditable log. Tab-visibility logging is noisy on mobile and trivial to evade.
I would omit it from the first release and never use it automatically to alter
a result. The format cannot prevent screenshots, a second device, advance
question sharing, or help from another person; owner communications should not
claim that it can.

## Question bank and grading

Import one immutable **question-bank version** before opening. Validate exactly
50 unique positions, nonblank prompts, canonical answers, aliases, and stable
question IDs. Produce a review screen and printable export for proofreading.
Once any official attempt starts, edits create a new bank version and are
blocked unless the owner explicitly voids/restarts the affected run. This
freeze is more important than a rich question editor.

Normalization must be conservative and question-scoped. Keep the raw answer
forever and derive separate comparison keys. Lowercasing, Unicode
normalization, whitespace collapse, and selected punctuation folding are safe
defaults. Removing articles, punctuation, or diacritics globally can change a
meaningful answer; those transformations should be explicit per question or
represented as aliases.

Autograding should have only two decisive outcomes:

- exact match to an approved canonical/alias comparison key: provisionally
  correct;
- blank: incorrect.

Everything else is unresolved until reviewed. Levenshtein or token similarity
may prioritize likely near misses, but should not auto-mark them correct or
incorrect. With only a few hundred players, false confidence is costlier than
reviewing a few extra variants.

### Review by answer variant

Grouping is the right work-saving primitive **within one question and one bank
version**, but it should be a review decision/rule, not a destructive bulk
update to answer rows. A review record maps `(question_version,
comparison_key)` to `correct`, `incorrect`, or `needs_individual_review`, with
reviewer, time, note, and revision history. Scores are derived from the current
rule set.

The screen should show the canonical answer, aliases, raw spellings and counts,
plus similarity hints. A reviewer can expand the affected players when context
matters. Optimistic locking prevents two graders from silently overwriting one
another. Changing a ruling invalidates the current result snapshot and records
who changed it.

This preserves identical treatment without pretending every lossy-normalized
string is semantically identical.

## Ranking and publication

Score is one point per non-voided correct question. Support voiding a question
from day one via an `included_in_score` flag and an audited reason; do not
"rescale" scores, just rank on the remaining maximum.

I do **not** recommend total correct-answer time as the default tiebreak.
Network and rendering delay cannot be measured away completely, and speed over
only correct answers can create unintuitive incentives. More importantly, the
owner has fixed top N but has not fixed a tie policy. Product code should not
quietly decide who advances.

Before opening, the owner must select one documented policy:

1. advance everyone tied at the cut line (best if Saturday capacity permits);
2. run a separate supervised tiebreak among cut-line ties; or
3. explicitly approve a recorded prelim timing metric despite its limitations.

Until then, results show score bands and identify the unresolved cut-line tie.
N itself is configuration, never hard-coded.

When grading is complete, create an immutable **result snapshot** containing
bank version, grading-rule revision, voided questions, tie policy, N, ranked
rows, application Git commit, and publication timestamp. Export CSV from that
snapshot. Later grading changes create a new snapshot rather than silently
changing the already-used cut list.

## Reliability and operational controls

The proposal needs more emphasis on failure containment:

- database constraints for one exposure per attempt/question, one final answer,
  unique question positions, and one official attempt generation;
- CSRF protection, secure cookies, strict admin authorization, output escaping,
  parameterized queries, request-size limits, and rate limiting;
- question prompts and answer keys accessible only through authorized server
  routes, never a public JSON/static bundle; answer keys on admin routes only;
- structured request IDs and an append-only domain audit log, while suppressing
  invitation tokens and raw answers from ordinary web logs;
- `/health` and `/ready` checks that include database reachability without
  leaking details;
- automated database backups plus an actual restore rehearsal before invites;
- a CSV safety export of players, attempts, raw answers, timestamps, and
  grading rules after the window closes;
- release banner in admin showing Git commit, bank version, environment, and
  database migration version;
- production smoke test using excluded staff accounts after every deploy;
- freeze production deploys and question-bank changes once official play
  begins except for a documented emergency procedure.

Also test browser lifecycle behavior: iOS backgrounding, Android timer
throttling, refresh, Back, double submit, offline/online transitions, expired
cookies, duplicate tabs, and a deploy while a question is in flight. The
countdown must be derived from absolute deadline state after wake-up, not by
assuming interval callbacks fire on time.

Accessibility is not polish: the timer must not steal focus, status updates
must be announced sensibly, the answer remains keyboard usable, contrast and
tap targets must pass, and reduced-motion preferences must be respected. Turn
off autocorrect/autocapitalize/spellcheck, but verify that this does not make
assistive input unusable.

## Minimum admin surface

Ship only:

- player CSV import/export and token/link generation;
- question CSV import, validation, freeze, and proofing view;
- progress counts and per-player attempt detail;
- audited grant-restart action;
- question-scoped variant review queue;
- score bands, unresolved cut-line ties, result snapshot, and CSV export;
- runtime/bank/release status plus backup status.

Admin authentication should use one proven external identity mechanism or a
small pre-provisioned account set with strong generated passwords. Do not build
password reset, roles, or an admin user-management product during event week.

## What to cut

Cut from the first production release:

- built-in invitation and reminder email; export for mail merge instead;
- self-service invitation recovery;
- tab-switch/visibility suspicion scoring;
- player question flags;
- editable question authoring UI beyond import/proof/freeze;
- per-player printable answer sheets (retain/export the underlying data);
- elaborate analytics beyond variant counts, score distribution, and
  per-question correct/blank/unresolved rates;
- live WebSockets, queues, Redis, microservices, and scheduled timeout jobs;
- automatic fuzzy verdicts;
- a general-purpose scoring/dashboard integration for Saturday.

Keep two cheap safety features: one unscored practice question before the real
attempt, and audited question voiding. Both directly reduce event-night risk.

## Delivery phases and estimate

The calendar deadline must be supplied before a calendar-date promise can be
credible. For one experienced agent/developer with prompt owner feedback, the
minimum safe build is approximately **4 focused days plus a rehearsal day**;
question authoring and owner review proceed in parallel.

| Phase | Elapsed effort | Exit condition |
|---|---:|---|
| Decision spike | 2–4 hours | Host, exact deadline, N/tie policy, question format, Ready behavior, graders, and admin auth chosen; production skeleton deploys with DB/backup/commit banner. |
| Core state machine | 1 day | Invite redemption, practice, Ready/question/draft/finalize/resume/complete flow works with transactional idempotency and abandonment behavior. |
| Grading and results | 1 day | Validated bank/player imports, conservative autograding, variant review, voiding, score bands, snapshots, and exports work. |
| Hardening | 1 day | Security controls, restart generations, migration/deploy rollback, backups/restore, monitoring, and failure-path tests pass. |
| Mobile/rehearsal | 1 day | Real iPhone/Android runs, latency measurements, concurrent simulated players, grader rehearsal, and cut-list drill pass. |

If fewer than four focused build days remain, reduce scope further rather than
compressing rehearsal: use external mail merge, one grader, no fuzzy
prioritization, CSV-only imports, and the minimal dashboard above. If fewer than
two build days plus one rehearsal day remain, I would not responsibly launch a
new custom timed qualifier; use an established quiz platform or change the
event procedure.

## Decisions needed before implementation

Implementation should start only after the owner answers these load-bearing
items:

1. Exact close date/year and start-cutoff wording.
2. Number invited, N, and cut-line tie policy.
3. Ready before every question, or only before Q1.
4. Source/format and final owner of the 50 questions and aliases.
5. Grader count and admin identity mechanism.
6. Whether a new paid hosting/database account can be created immediately.
7. Whether a restart means a full replacement attempt from Q1 (recommended).

Everything else can safely proceed under the assumptions in this document.
