# Current handoff

Last updated: 2026-07-28.

## Recently completed

- Built, deployed, and live-tested the My Tickets v0.5.3 system: passwordless
  ticket retrieval, printable QR tickets, mobile staff scanner, check-in roster,
  and editable allocated tickets.
- Restored event signup matching after the Knock Out Quiz title change.
- Built and activated Attendee Email v0.3.0. A read-only recipient preview found
  180 unique ticket/allocated addresses at test time; no email blast was sent.
- Added and deployed the static scores-site coming-soon page, then iterated its
  copy twice (subheading and info-card text); both edits were pushed as part of
  `8bc6ab4` and are **live verified** at `https://scores.trivianationals.org`.
- Verified read-only explicit-FTPS access to the scores account.
- Added this shared Claude/Codex project-memory and checkpoint system
  (`8bc6ab4`).
- 2026-07-28: Claude independently pulled `main`, read `AGENTS.md` → `PROJECT.md`
  → `docs/CURRENT-STATE.md` → `docs/DEPLOYMENT.md` → `docs/HANDOFF.md`, and ran
  `scripts/project-checkpoint.sh --check` — passed cleanly with the local tree
  already matching `origin/main` at `8bc6ab4`. Confirms the shared-memory
  handoff round-trips correctly between Codex and Claude on this machine.

## Immediate next steps

- Use `/project-checkpoint` in Claude, or ask Codex for a complete project
  checkpoint, after meaningful future releases.
- Pull `main` on the MacBook before continuing work there.
- Scope the real scoring system before replacing the static placeholder.
- Complete the coordinated `SYNC_SECRET` rotation (see Known cautions) before
  deploying the updated Event Signups Apps Script.

## Known cautions

- Local untracked customer exports and working assets predate this checkpoint;
  they are intentionally excluded.
- The tracked Event Signups Apps Script was changed locally to obtain
  `SYNC_SECRET` from Script Properties. This is not deployed. Rotate the
  formerly committed secret when setting the property and update the matching
  WordPress setting; the old value remains in Git history.
- Other Apps Script working copies may contain live secret values; only
  reviewed, intentionally tracked code belongs in Git.
- Do not infer that every source directory is active in production. In
  particular, confirm the standalone Event Schedule plugin's role before
  deployment.
- Test-email and attendee-send actions produce real external messages and
  require deliberate authorization.
