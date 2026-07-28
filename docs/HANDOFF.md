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
- 2026-07-28: Built and **deployed to production** a team roster picker for
  team-based event signups: a filterable checkbox list of registered ticket
  holders, usable by both a WP admin ("Team Rosters" submenu under
  `trivia-desc-editor`) and the registering team captain (a "Choose Team
  Members" button on their `/manage-signups/` card, opening a dedicated
  picker screen on the same route). Once someone is assigned to a team for an
  event, they're greyed out for every other team in that event. Shipped as
  `tn_tickets_attendee_roster()` in My Tickets (now live at 0.5.4) plus the
  picker/assignment logic in Event Schedule Manager (now live at 2.1). Both
  deploys were hash-verified against the live files after upload.
- 2026-07-28: Before deploying the Event Schedule Manager change, a fresh
  `diff` against production found real drift never captured in Git: the
  "All Trivia: The Gathering" waitlist feature (`isWaitlist`,
  `tn_tde_signup_is_ttg_event()`, waitlist-specific flight/button copy) and a
  richer `tn_tde_render_description_html()` helper both exist live but were
  never pulled back. Reconciled by pulling live as the base, isolating the
  roster-picker patch via `git diff` against the pre-work commit, and
  reapplying just that patch (it applied with zero fuzz — confirms the
  feature never touched the drifted code) before deploying. Git now matches
  production for this file, including the previously-undocumented waitlist
  feature.
- 2026-07-28 incident (resolved): the first FTP upload attempt for
  `trivia-nationals-my-tickets.php` failed with `451 Error during read from
  data connection` **after** the full file was sent, leaving the live file
  **empty** for a few minutes (My Tickets fully down: ticket retrieval,
  check-in scanner, allocated tickets). Root cause: this host's FTPS data
  channel intermittently mishandles TLS 1.3 (seen negotiated in the failing
  transfer); forcing `--tlsv1.2 --tls-max 1.2` fixed it immediately. Restored
  the pre-incident content from a local backup taken seconds earlier, then
  redeployed the feature version — both hash-verified. `scripts/wp-plugin-ftps.sh`
  now forces TLS 1.2 on both `fetch_live`/`push_live` to prevent a repeat.
- 2026-07-28: the user tried the admin "Team Rosters" screen live and found
  two issues, both now fixed, committed, pushed, and **deployed**: (1) the
  page rendered a full attendee-list picker for every team at once — replaced
  with a single dropdown (grouped by event, `(count)` per team) driving one
  shared panel, GET-reload on change, no AJAX; (2) the attendee list showed
  apparent duplicate names — `tn_tickets_attendee_roster()`'s `name` field
  had a backwards ternary that preferred the order's shared billing name
  over the per-seat Preferred Name, so every seat from a multi-ticket order
  showed the identical billing name. Fixed to just use `$preferred_name`
  (which already falls back to the billing name itself when unset). My
  Tickets is now live at **0.5.5**, Event Schedule Manager at **2.2**, both
  hash-verified. Pre-deploy drift check on both files found no unexpected
  production changes this time.

## Immediate next steps

- Use `/project-checkpoint` in Claude, or ask Codex for a complete project
  checkpoint, after meaningful future releases.
- Pull `main` on the MacBook before continuing work there.
- Scope the real scoring system before replacing the static placeholder.
- Complete the coordinated `SYNC_SECRET` rotation (see Known cautions) before
  deploying the updated Event Signups Apps Script.
- Confirm the name fix actually resolves the duplicate-looking entries
  against a real multi-ticket order — it only surfaces a distinct Preferred
  Name if one was actually captured per seat at checkout; if the checkout
  only ever stores one shared value for a whole multi-quantity line item,
  seats will still look identical (a data-capture gap, not a code bug), and
  the next step would be a cosmetic "(2 of 4)"-style disambiguator.
- Otherwise, live-test the team roster picker end to end: the admin
  screen's new dropdown/panel, the captain "Choose Team Members" flow
  (including the confirmation email), and cross-team exclusion with two
  real team signups on the same event.
- Watch for any recurrence of the `451`/TLS 1.3 FTP data-connection issue on
  future deploys, now that `wp-plugin-ftps.sh` forces TLS 1.2 — if it still
  recurs, the cause isn't (only) TLS version and needs more digging.

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
