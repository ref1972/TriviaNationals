import { DatabaseSync } from "node:sqlite";

export function openStore(path) {
  const db = new DatabaseSync(path);
  db.exec(`
    PRAGMA journal_mode = WAL;
    CREATE TABLE IF NOT EXISTS delivery_audit (
      id INTEGER PRIMARY KEY,
      client_id TEXT NOT NULL,
      recipient_hash TEXT NOT NULL,
      attempted_at TEXT NOT NULL DEFAULT (datetime('now')),
      accepted INTEGER NOT NULL CHECK (accepted IN (0, 1)),
      smtp_code INTEGER,
      enhanced_status TEXT,
      provider_message_id TEXT,
      error_class TEXT
    );
    CREATE INDEX IF NOT EXISTS delivery_audit_recent
      ON delivery_audit (attempted_at, accepted);
  `);

  const acceptedStatement = db.prepare(`
    SELECT count(*) AS total
    FROM delivery_audit
    WHERE accepted = 1 AND attempted_at >= datetime('now', '-24 hours')
  `);
  const acceptedHourStatement = db.prepare(`
    SELECT count(*) AS total
    FROM delivery_audit
    WHERE accepted = 1 AND attempted_at >= datetime('now', '-1 hour')
  `);
  const lastAcceptedStatement = db.prepare(`
    SELECT attempted_at
    FROM delivery_audit
    WHERE accepted = 1
    ORDER BY id DESC
    LIMIT 1
  `);
  const insertStatement = db.prepare(`
    INSERT INTO delivery_audit
      (client_id, recipient_hash, accepted, smtp_code, enhanced_status,
       provider_message_id, error_class)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `);

  return {
    acceptedLast24Hours() {
      return Number(acceptedStatement.get().total);
    },
    acceptedLastHour() {
      return Number(acceptedHourStatement.get().total);
    },
    lastAcceptedAtMs() {
      const row = lastAcceptedStatement.get();
      return row?.attempted_at ? Date.parse(`${row.attempted_at}Z`) : 0;
    },
    record(entry) {
      insertStatement.run(
        entry.clientId,
        entry.recipientHash,
        entry.accepted ? 1 : 0,
        entry.smtpCode ?? null,
        entry.enhancedStatus ?? null,
        entry.messageId ?? null,
        entry.errorClass ?? null,
      );
    },
    close() {
      db.close();
    },
  };
}
