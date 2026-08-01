/**
 * Thin wrapper over node:sqlite.
 *
 * node:sqlite is built into Node 24, so there is no native build step and the
 * local and deployed databases are byte-identical. It is still flagged
 * experimental, meaning its *API* may shift across Node majors. Everything
 * that touches SQLite goes through this module so that swapping in
 * better-sqlite3 is a change to one file rather than a rewrite.
 */
import { DatabaseSync } from 'node:sqlite';
import { readFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { mkdirSync } from 'node:fs';
import { DB_PATH } from './config.ts';

const here = dirname(fileURLToPath(import.meta.url));

export type Row = Record<string, unknown>;

let handle: DatabaseSync | null = null;

export function db(): DatabaseSync {
  if (handle) return handle;

  const path = resolve(DB_PATH);
  mkdirSync(dirname(path), { recursive: true });

  handle = new DatabaseSync(path);
  handle.exec(readFileSync(join(here, 'schema.sql'), 'utf8'));
  return handle;
}

export function all(sql: string, ...params: unknown[]): Row[] {
  return db().prepare(sql).all(...(params as never[])) as Row[];
}

export function get(sql: string, ...params: unknown[]): Row | undefined {
  return db().prepare(sql).get(...(params as never[])) as Row | undefined;
}

/** Returns the number of rows actually changed, which several call sites use
 *  to detect that they lost a race and should not act. */
export function run(sql: string, ...params: unknown[]): number {
  const result = db().prepare(sql).run(...(params as never[]));
  return Number(result.changes);
}

/**
 * Wraps fn in an IMMEDIATE transaction. IMMEDIATE takes the write lock up
 * front, so two concurrent requests for the same player serialize here rather
 * than discovering a conflict halfway through.
 */
export function transact<T>(fn: () => T): T {
  const handle = db();
  handle.exec('BEGIN IMMEDIATE');
  try {
    const out = fn();
    handle.exec('COMMIT');
    return out;
  } catch (err) {
    try {
      handle.exec('ROLLBACK');
    } catch {
      // A rollback failure would mask the original error; the original wins.
    }
    throw err;
  }
}

/** ISO-8601 UTC with milliseconds. The only timestamp format in the database. */
export function nowIso(): string {
  return new Date().toISOString();
}

export function msSince(iso: string): number {
  return Date.now() - Date.parse(iso);
}

export function logEvent(
  kind: string,
  opts: {
    playerId?: number | null;
    position?: number | null;
    detail?: string | null;
    ip?: string | null;
    userAgent?: string | null;
  } = {},
): void {
  run(
    `INSERT INTO events (player_id, position, kind, detail, ip, user_agent, at)
     VALUES (?, ?, ?, ?, ?, ?, ?)`,
    opts.playerId ?? null,
    opts.position ?? null,
    kind,
    opts.detail ?? null,
    opts.ip ?? null,
    opts.userAgent ?? null,
    nowIso(),
  );
}
