# Current handoff

Last updated: 2026-07-29.

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
- 2026-07-28: the duplicate-name symptom turned out to be a real, deeper
  bug the 0.5.5 fix hadn't fully addressed: `preferred_name($item, $order)`
  used `get_meta($key, true)`, which only ever returns the *first* value
  for a meta key — so every seat on a quantity>1 line item still shared
  one name, at all 4 call sites (roster, check-in roster, printable/QR
  tickets, QR-scan validation), not just the roster picker. Replaced with
  `preferred_names_for_item()`, which reads *all* values per key
  (`get_meta($key, false)`) and assigns one per seat. Also added, used
  once, then removed a temporary admin action to split a real order
  (19505, bought as one quantity-2 line item with both names crammed into
  one field as `"Will Webster/Britton Webster"`) into two distinct
  per-seat values — confirmed live via browser automation: the order now
  shows two separate `Preferred Name for Ticket/Badge` entries, and both
  "Will Webster" and "Britton Webster" now appear as separate, individually
  selectable rows in the Team Rosters picker. My Tickets is now live at
  **0.5.8**.
  - Two bugs surfaced and were fixed mid-rollout: `get_meta($key, false)`
    returns an array of `WC_Meta_Data` **objects**, not raw values —
    casting one directly to string fatals, which is what caused the
    Team Rosters "critical error" the user hit; and the one-off fix's
    button was a `<button type="submit">` inside a nested `<form>`,
    which WordPress's Edit Order screen already wraps in its own `<form>`
    — nested forms get silently dropped by the browser, so the button did
    nothing on the first click. Fixed by using a plain nonce'd `<a>` link
    instead (the same pattern `tn_tde_signup_status_action_url()` already
    uses in `trivia-desc-editor.php` for one-click admin actions).
  - Confirmed live via browser automation that allocated tickets
    (`tn_alloc_ticket` CPT) were already merged into the roster by
    `attendee_roster()` — all 9 current allocated-ticket holders appear in
    the Team Rosters picker (203 total rows). No code change was needed
    for this; it was a verification, not a bug.
- 2026-07-28: added a reusable **"Ticket Names"** admin screen (My Tickets,
  under the WooCommerce menu) — look up any order by ID, see each ticket
  seat's current Preferred Name, edit and save (only that meta key is
  touched). Replaces one-off scripts for future cases like order 19505.
  Used it to fix order 18490 (Michael Conrad → Dave Legler), confirmed
  live. Also confirmed WooCommerce's own Edit Order → Billing → Email
  address field is already natively editable — no new code needed for
  changing an order's billing email (it's per-order, not per-seat; the
  user confirmed per-order is sufficient).
- 2026-07-28: diagnosed (not a bug, confirmed intentional) why a
  "Semi-Finals" Quiz Bowl team showed 0 players despite its captain
  clearly having assigned herself somewhere — she has two separate Quiz
  Bowl signups (different flights) and assigned her seat to the *other*
  one. Cross-team exclusion is scoped by base event title, not by flight,
  so the same person can't be on two flight-rosters of the same event —
  user confirmed this is the desired behavior. Cancelled the redundant
  Semi-Finals signup via the existing admin "Cancel signup" row action (no
  code needed).
- 2026-07-28: three more Team Rosters upgrades, all in Event Schedule
  Manager (now v2.4), deployed and verified live: (1) an editable **Team
  Name** field on the admin roster panel; (2) an **"Export All Rosters
  (CSV)"** link (`Event, Team Name, Captain, Player Name` columns, one
  combined file); (3) a **public `/team-rosters/` page** listing every
  team event's teams, captains, and player names (confirmed via automated
  scan: zero email addresses in the page source). Also ran a one-time data
  update — 29 solo (1-player) teams got their name prefixed with "FA: "
  (blank ones became "FA: {captain name}") via a temporary admin action,
  applied, confirmed live, then removed from source.
- 2026-07-28: added a per-event **summary table** to the admin Team Rosters
  page (Total Teams / Total Players / Of Which Solo (Free Agent) columns,
  with a note clarifying the total already includes solo teams), and fixed
  a real N+1 performance bug the public `/team-rosters/` page had from the
  start: it was rebuilding the entire attendee roster and re-querying "taken
  by" state once per team (~75 times per page view). Fixed the root cause
  (resolve each team's assigned player ids through a single id→name map
  built once per page load) and added a 1-hour-TTL transient cache on top,
  invalidated immediately by the three functions that change what the page
  shows (roster save, signup status change, new signup). Verified ~8x
  speedup (3.5s → ~420ms) with identical rendered content, and verified the
  cache actually invalidates on a real roster edit. Event Schedule Manager
  is now v3.3.
- 2026-07-28: added a soft-**delete** row action for allocated tickets in
  My Tickets (`wp_trash_post()`-based, nonce-checked, `manage_woocommerce`
  capability), now live at **v0.6.0**.
- 2026-07-28: triaged a forwarded WordPress fatal-error notification email
  referencing My Tickets v0.5.6, line 544 — confirmed **stale/already
  resolved**: production is at v0.6.0, the referenced line is now just a
  comment, and the exact failing URL from the report loads without error.
  No action needed; this was a delayed notification about an incident
  already fixed earlier the same day.
- 2026-07-29: **migrated site email to Google Workspace**, replacing the
  personal-Gmail-alias relay setup. Root cause of "Email Attendees" test
  sends failing: (1) the Apps Script's Gmail account had hit its 100/day
  consumer sending quota, and (2) even successful sends were routing
  through HostGator's own SMTP server anyway, because the `info@` Gmail
  "send mail as" alias was configured to relay through
  `mail.trivianationals.org` rather than send natively — confirmed via a
  real bounce showing that exact path (`wartburg.websitewelcome.com` →
  `450 ... AUP#MXRT`).
  - DNS on `trivianationals.org` (cPanel Zone Editor): MX repointed to
    Google's 5 MX records; SPF gained `include:_spf.google.com`; DKIM added
    at `google._domainkey` using Google's **1024-bit** key option (cPanel's
    Zone Editor TXT field is a single-line ~255-char input with no
    multi-string support — the 2048-bit key silently truncated).
  - `info@trivianationals.org` is now a real Workspace mailbox (display
    name "Trivia Nationals"). The old HostGator forwarders (`info2@`,
    `leeann@`, `marketing@`, plus `info@`'s own dual-forward to two
    personal Gmail addresses) were recreated as Workspace email aliases on
    the `info@` user plus per-address Gmail filters forwarding externally
    (Google Groups were tried first but new Workspace orgs default to
    blocking external group members; the alias+filter approach avoided
    that policy entirely).
  - The Event Signups Apps Script (`google-apps-script/event-signups/Code.gs`)
    is now deployed under a **new** project owned directly by `info@`
    (sharing+redeploying the old personal-account-owned project failed with
    a bare "You do not have permission to perform this action" — Apps
    Script blocks Web App deployment by a non-owner even with Editor
    access). `SYNC_SECRET` was **rotated** into this new project's Script
    Properties rather than reused, closing out the long-standing
    "committed secret never rotated" caution. WordPress's **Event Schedule
    Manager → Signup Settings** admin page now points at the new
    deployment URL and secret.
  - Verified end to end twice — a direct API test and a real send through
    WordPress's actual `/event-signups/` "Email My Signups" flow — by
    inspecting full message headers: `dkim=pass`, `spf=pass`, `dmarc=pass`,
    delivered via `gmailapi.google.com` with zero HostGator hop in the
    `Received:` chain.
  - Not done in this migration: `wp_mail()` itself (the fallback path, and
    the direct path for other mail like WooCommerce order emails) still
    goes through HostGator's broken local `mail()`. Two stray Apps Script
    deployments from troubleshooting (under the old personal-account
    project) are still live but unused.

## Immediate next steps

- Use `/project-checkpoint` in Claude, or ask Codex for a complete project
  checkpoint, after meaningful future releases.
- Pull `main` on the MacBook before continuing work there.
- Scope the real scoring system before replacing the static placeholder.
- Consider pointing `wp_mail()` itself at Workspace SMTP directly (e.g. WP
  Mail SMTP plugin) so the fallback path and other mail (WooCommerce order
  emails) also drop the HostGator dependency, not just the Apps Script path.
- Delete the two unused Apps Script Web App deployments left over from the
  Workspace migration's troubleshooting (owned by the old personal-account
  project) — harmless but tidy up when convenient.
- Live-test the team roster picker's remaining untested path: the captain
  "Choose Team Members" flow on `/manage-signups/` (including the
  confirmation email), and cross-team exclusion with two real team
  signups on the same event. The admin screen and the name-per-seat fix
  are now confirmed live.
- Watch for any recurrence of the `451`/TLS 1.3 FTP data-connection issue on
  future deploys, now that `wp-plugin-ftps.sh` forces TLS 1.2 — if it still
  recurs, the cause isn't (only) TLS version and needs more digging.
- If `get_meta($key, false)` comes up again in this codebase, remember it
  returns `WC_Meta_Data` objects, not raw values — extract `->value`
  (see `TN_My_Tickets::meta_values_for_key()` for the defensive pattern).

## Known cautions

- Local untracked customer exports and working assets predate this checkpoint;
  they are intentionally excluded.
- The Event Signups Apps Script now runs deployed, under Script Properties,
  with a rotated `SYNC_SECRET` (see docs/HANDOFF.md's 2026-07-29 entry above).
  The formerly committed secret is no longer in use but still exists in Git
  history; it is not sensitive to rotate again if ever needed.
- Other Apps Script working copies may contain live secret values; only
  reviewed, intentionally tracked code belongs in Git.
- Do not infer that every source directory is active in production. In
  particular, confirm the standalone Event Schedule plugin's role before
  deployment.
- Test-email and attendee-send actions produce real external messages and
  require deliberate authorization.
