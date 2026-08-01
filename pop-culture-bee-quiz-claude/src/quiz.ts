/**
 * Server-authoritative quiz engine.
 *
 * The governing rule: the server decides what question a player is on and how
 * much time is left on it. The client is told `remainingMs`, never a flat 20
 * seconds, so reloading the page returns the same question with the time
 * actually left rather than a fresh window. Nothing here trusts a clock,
 * timestamp, or position supplied by the browser.
 *
 * An `answers` row is created the instant a question is served. That row's
 * `served_at` is the authoritative start of the window, and the row is
 * finalized exactly once -- by a submission, or by the deadline passing.
 */
import { QUESTION_DURATION_MS, SUBMIT_GRACE_MS, TOTAL_QUESTIONS } from './config.ts';
import { all, get, logEvent, msSince, nowIso, run, transact, type Row } from './db.ts';

export type QuestionState = {
  status: 'question';
  position: number;
  prompt: string;
  remainingMs: number;
  draft: string;
  totalQuestions: number;
};

export type State =
  | QuestionState
  | { status: 'finished' }
  | { status: 'not_started' };

export type SubmitOutcome =
  | { result: 'recorded'; expired: boolean }
  /** The row was already finalized -- a double submit, or the deadline won. */
  | { result: 'already_finalized' }
  | { result: 'no_such_question' };

function inflight(playerId: number): Row | undefined {
  return get(
    `SELECT * FROM answers
      WHERE player_id = ? AND submitted_at IS NULL
      ORDER BY position LIMIT 1`,
    playerId,
  );
}

function remainingFor(servedAt: string): number {
  return QUESTION_DURATION_MS - msSince(servedAt);
}

/**
 * Finalize an in-flight row whose window has closed. The recorded answer is
 * the last draft the server accepted before the deadline -- which is what
 * makes "whatever is in the answer field auto-submits" true even when the
 * browser died, closed, or lost its network mid-question.
 *
 * Idempotent: the `submitted_at IS NULL` guard means a concurrent submission
 * that got there first simply wins and this becomes a no-op.
 */
function finalizeExpired(row: Row): boolean {
  const changed = run(
    `UPDATE answers
        SET raw_answer = draft_answer,
            submitted_at = ?,
            elapsed_ms = ?,
            expired = 1,
            auto_submitted = 0
      WHERE id = ? AND submitted_at IS NULL`,
    nowIso(),
    QUESTION_DURATION_MS,
    row['id'],
  );
  if (changed > 0) {
    logEvent('question_expired', {
      playerId: row['player_id'] as number,
      position: row['position'] as number,
      detail: JSON.stringify({ recovered_draft: (row['draft_answer'] as string) !== '' }),
    });
  }
  return changed > 0;
}

/** Serves the next unserved question, or reports that the quiz is complete. */
function serveNext(playerId: number): State {
  const answered = get(
    `SELECT COALESCE(MAX(position), 0) AS max_pos FROM answers WHERE player_id = ?`,
    playerId,
  );
  const next = Number(answered?.['max_pos'] ?? 0) + 1;

  if (next > TOTAL_QUESTIONS) {
    run(
      `UPDATE players SET finished_at = ? WHERE id = ? AND finished_at IS NULL`,
      nowIso(),
      playerId,
    );
    logEvent('quiz_finished', { playerId });
    return { status: 'finished' };
  }

  const question = get(`SELECT * FROM questions WHERE position = ?`, next);
  if (!question) {
    // Fewer questions loaded than TOTAL_QUESTIONS expects. Treat the quiz as
    // complete rather than trapping the player on a blank screen.
    run(
      `UPDATE players SET finished_at = ? WHERE id = ? AND finished_at IS NULL`,
      nowIso(),
      playerId,
    );
    return { status: 'finished' };
  }

  const servedAt = nowIso();
  run(
    `INSERT INTO answers (player_id, position, served_at) VALUES (?, ?, ?)`,
    playerId,
    next,
    servedAt,
  );
  logEvent('question_served', { playerId, position: next });

  return {
    status: 'question',
    position: next,
    prompt: question['prompt'] as string,
    remainingMs: QUESTION_DURATION_MS,
    draft: '',
    totalQuestions: TOTAL_QUESTIONS,
  };
}

/**
 * The single entry point for "what should this player see right now?".
 *
 * Resolves in one IMMEDIATE transaction so that a page load racing an
 * auto-submit cannot serve the same question twice or skip one.
 */
export function currentState(playerId: number): State {
  return transact(() => {
    const player = get(`SELECT * FROM players WHERE id = ?`, playerId);
    if (!player) return { status: 'not_started' };
    if (!player['started_at']) return { status: 'not_started' };
    if (player['finished_at']) return { status: 'finished' };

    const row = inflight(playerId);
    if (row) {
      const remaining = remainingFor(row['served_at'] as string);
      if (remaining > 0) {
        // Same question, real remaining time. A refresh buys nothing.
        const question = get(`SELECT * FROM questions WHERE position = ?`, row['position']);
        return {
          status: 'question',
          position: row['position'] as number,
          prompt: (question?.['prompt'] as string) ?? '',
          remainingMs: remaining,
          draft: (row['draft_answer'] as string) ?? '',
          totalQuestions: TOTAL_QUESTIONS,
        };
      }
      // Window closed while the player was away. Per the abandonment policy
      // they lose exactly this one question, then resume at the next.
      finalizeExpired(row);
    }

    return serveNext(playerId);
  });
}

/**
 * Records a submission. `position` is checked against the server's own notion
 * of the in-flight question, so a replayed or hand-crafted post cannot answer
 * a question the player has not been served.
 */
export function submitAnswer(
  playerId: number,
  position: number,
  text: string,
  autoSubmitted: boolean,
): SubmitOutcome {
  return transact(() => {
    const row = get(
      `SELECT * FROM answers WHERE player_id = ? AND position = ?`,
      playerId,
      position,
    );
    if (!row) return { result: 'no_such_question' };
    if (row['submitted_at']) return { result: 'already_finalized' };

    const servedAt = row['served_at'] as string;
    const elapsed = msSince(servedAt);
    const withinWindow = elapsed <= QUESTION_DURATION_MS + SUBMIT_GRACE_MS;

    // Inside the window (plus latency slack) the submitted text stands.
    // Outside it, the browser's clock was wrong, paused, or tampered with, so
    // the pre-deadline draft is what counts.
    const finalText = withinWindow ? text : ((row['draft_answer'] as string) ?? '');

    const changed = run(
      `UPDATE answers
          SET raw_answer = ?,
              submitted_at = ?,
              elapsed_ms = ?,
              auto_submitted = ?,
              expired = ?
        WHERE id = ? AND submitted_at IS NULL`,
      finalText,
      nowIso(),
      Math.min(elapsed, QUESTION_DURATION_MS),
      autoSubmitted ? 1 : 0,
      withinWindow ? 0 : 1,
      row['id'],
    );

    if (changed === 0) return { result: 'already_finalized' };

    logEvent('answer_submitted', {
      playerId,
      position,
      detail: JSON.stringify({
        elapsed_ms: elapsed,
        auto: autoSubmitted,
        late: !withinWindow,
        blank: finalText.trim() === '',
      }),
    });

    return { result: 'recorded', expired: !withinWindow };
  });
}

/**
 * Accepts an autosaved draft. Rejected once the window has closed, which keeps
 * `draft_answer` a strictly pre-deadline value and therefore safe to promote
 * to the final answer on expiry.
 */
export function saveDraft(playerId: number, position: number, text: string): boolean {
  return transact(() => {
    const row = get(
      `SELECT * FROM answers
        WHERE player_id = ? AND position = ? AND submitted_at IS NULL`,
      playerId,
      position,
    );
    if (!row) return false;
    if (msSince(row['served_at'] as string) > QUESTION_DURATION_MS + SUBMIT_GRACE_MS) return false;

    run(
      `UPDATE answers SET draft_answer = ?, draft_at = ?
        WHERE id = ? AND submitted_at IS NULL`,
      text,
      nowIso(),
      row['id'],
    );
    return true;
  });
}

export function startPlayer(playerId: number): void {
  run(
    `UPDATE players SET started_at = ? WHERE id = ? AND started_at IS NULL`,
    nowIso(),
    playerId,
  );
  logEvent('quiz_started', { playerId });
}

/**
 * Closes out any window that expired while its player was away. Scoring must
 * run this first, otherwise an abandoned session leaves a question in flight
 * forever and the player's total is understated.
 */
export function finalizeStaleSessions(): number {
  return transact(() => {
    const rows = all(`SELECT * FROM answers WHERE submitted_at IS NULL`);
    let closed = 0;
    for (const row of rows) {
      if (remainingFor(row['served_at'] as string) <= 0 && finalizeExpired(row)) closed += 1;
    }
    return closed;
  });
}

export function progressFor(playerId: number): { answered: number; total: number } {
  const row = get(
    `SELECT COUNT(*) AS n FROM answers WHERE player_id = ? AND submitted_at IS NOT NULL`,
    playerId,
  );
  return { answered: Number(row?.['n'] ?? 0), total: TOTAL_QUESTIONS };
}
