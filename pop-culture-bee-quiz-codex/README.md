# Pop Culture Bee Quiz — Codex version

Independent implementation. This directory does not share code or runtime data
with `pop-culture-bee-quiz-claude/`.

## Local setup

1. Copy `.env.example` to `.env` and replace both secrets.
2. Run `npm install`.
3. Run `npm run dev`.
4. Open `http://localhost:3100/admin` and sign in with `ADMIN_PASSWORD`.

The configured cutoff is Thursday, August 6, 2026 at 11:59pm Central
(`2026-08-07T04:59:00.000Z`). Players who start before it may finish afterward.

Do not commit `.env`, the SQLite database, invitation exports, or player data.
