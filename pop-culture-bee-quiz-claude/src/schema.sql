-- Pop Culture Bee preliminary quiz schema.
-- All timestamps are ISO-8601 UTC strings with milliseconds.

PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS players (
  id                 INTEGER PRIMARY KEY,
  email              TEXT    NOT NULL UNIQUE,   -- always stored lowercased
  display_name       TEXT,
  token_hash         TEXT    UNIQUE,            -- sha256 of magic-link token (Phase 2)
  is_test            INTEGER NOT NULL DEFAULT 0,-- excluded from live results
  invited_at         TEXT,
  started_at         TEXT,
  finished_at        TEXT,
  restart_granted_at TEXT,
  created_at         TEXT    NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

CREATE TABLE IF NOT EXISTS questions (
  position         INTEGER PRIMARY KEY,          -- 1..50, also the display order
  prompt           TEXT    NOT NULL,
  canonical_answer TEXT    NOT NULL,
  aliases          TEXT    NOT NULL DEFAULT '[]',-- JSON array of accepted variants
  voided_at        TEXT,                          -- non-null: excluded from scoring
  void_reason      TEXT
);

-- One row per player per question. Created at the moment the question is
-- served, which is what makes served_at authoritative, and finalized exactly
-- once when the player submits or the deadline passes.
CREATE TABLE IF NOT EXISTS answers (
  id             INTEGER PRIMARY KEY,
  player_id      INTEGER NOT NULL REFERENCES players(id),
  position       INTEGER NOT NULL REFERENCES questions(position),
  served_at      TEXT    NOT NULL,
  draft_answer   TEXT    NOT NULL DEFAULT '',   -- last autosave accepted before the deadline
  draft_at       TEXT,
  raw_answer     TEXT,                           -- final text; NULL until finalized
  submitted_at   TEXT,                           -- NULL means still in flight
  elapsed_ms     INTEGER,
  auto_submitted INTEGER NOT NULL DEFAULT 0,     -- client timer fired rather than a click
  expired        INTEGER NOT NULL DEFAULT 0,     -- server finalized it; no client submit arrived
  auto_verdict   TEXT,                           -- correct | incorrect | review
  final_verdict  TEXT,                           -- correct | incorrect
  reviewed_by    TEXT,
  reviewed_at    TEXT,
  UNIQUE (player_id, position)
);

CREATE INDEX IF NOT EXISTS idx_answers_inflight
  ON answers (player_id) WHERE submitted_at IS NULL;

CREATE INDEX IF NOT EXISTS idx_answers_position ON answers (position);

-- Append-only. The record to consult when a cut-line result is contested.
CREATE TABLE IF NOT EXISTS events (
  id         INTEGER PRIMARY KEY,
  player_id  INTEGER,
  position   INTEGER,
  kind       TEXT NOT NULL,
  detail     TEXT,
  ip         TEXT,
  user_agent TEXT,
  at         TEXT NOT NULL DEFAULT (strftime('%Y-%m-%dT%H:%M:%fZ','now'))
);

CREATE INDEX IF NOT EXISTS idx_events_player ON events (player_id, at);
