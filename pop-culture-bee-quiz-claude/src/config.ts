/**
 * Central tunables. Everything time-related is milliseconds and UTC.
 */

/**
 * Per-question answering window. Overridable only so the test suite can run a
 * short window; production leaves it at 20 seconds.
 */
export const QUESTION_DURATION_MS = Number(process.env.QUESTION_DURATION_MS ?? 20_000);

/**
 * Slack allowed for a submission that was sent before the deadline but arrived
 * after it. This covers network latency, not clock tampering: the server still
 * decides, and anything past this is treated as an expiry.
 */
export const SUBMIT_GRACE_MS = Number(process.env.SUBMIT_GRACE_MS ?? 2_000);

/** Total questions in the quiz. */
export const TOTAL_QUESTIONS = 50;

/** How often the client posts a draft of the answer box. */
export const DRAFT_INTERVAL_MS = 2_000;

export const PORT = Number(process.env.PORT ?? 8080);
export const HOST = process.env.HOST ?? '127.0.0.1';

/** SQLite file. Kept out of the served directory. */
export const DB_PATH = process.env.DB_PATH ?? 'data/quiz.db';

/**
 * Entry deadline: players may not *start* after this instant, but a player
 * already in progress plays to completion (owner decision, 2026-07-31).
 * Stored UTC; displayed as Central. Null disables the check for local dev.
 */
export const START_DEADLINE_ISO = process.env.START_DEADLINE ?? null;

export const DEADLINE_DISPLAY = 'Thursday at 11:59pm Central';
