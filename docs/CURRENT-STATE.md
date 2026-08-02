# Current state

Last human review: 2026-07-29.

## Timed Quiz / Pop Culture Bee (standalone project)

- The application and its path-specific Git history were extracted on
  2026-08-01 to [ref1972/timedquiz](https://github.com/ref1972/timedquiz) and
  `/Users/russellefriedewald/Documents/Projects/TimedQuiz`. TriviaNationals no
  longer owns duplicate application source; it retains the shared Workspace
  Apps Script used by the quiz and other live systems. Nothing is deployed
  publicly and no real invitation email has been sent.
- Locally verified: CASS-style responsive light/dark UI, editable
  category/question/answer/aliases before attempts begin, server-authoritative
  timing and abandonment, CASS-compatible answer matching, grouped review,
  technical restarts, encrypted/rotatable invitation links, and ranking by
  score then total server-measured time across correct answers.
- Workspace invitation delivery is implemented as explicit test/quota/batch
  admin actions. It sends at most five per batch, persists per-player status,
  pauses on the first error/quota event, and never calls `wp_mail()`.
- The tracked Apps Script now has an authenticated `email_quota` action and
  returns quota metadata for `send_email`; this source change is **not deployed**
  to Apps Script yet.
- Automated verification currently passes 20/20 tests, TypeScript, checkpoint
  safety checks, fresh-database preflight, consistent SQLite backup, health,
  login, invitation redirect, and link rotation. Docker packaging exists but
  could not be built locally because Docker is not installed on this Mac.
- Deployment target selected 2026-08-01: the existing CASS DigitalOcean
  droplet, isolated from CASS at `https://bee.triviaworkshop.com` behind its
  own nginx virtual host and certificate. DNS, TLS, service, and application
  files have not been created yet.
- Remaining owner/external work: final question/category/alias review, final
  player list, cut-line N, DNS/TLS provisioning, production secrets,
  Apps Script redeploy, one real test email, phone/network rehearsal, and the
  deliberate real send.

## Live and verified

- The primary site is WordPress/WooCommerce at `trivianationals.org`.
- Shared Workspace Gmail API gateway rc2 is deployed at
  `https://mail.triviaworkshop.com`. WordPress remains
  on HostGator; only the isolated gateway runs on the existing droplet.
  It uses app-specific bearer credentials, a fixed server-side sender,
  single-recipient API requests, hashed-recipient audit records, a rolling
  safety ceiling, and no fallback. OAuth readiness succeeds with exactly
  `gmail.send`; the audit contains zero attempts/acceptances, so no mail has
  been sent. Timed Quiz rc23 now points to it and its no-send capacity check
  succeeds; WordPress still uses Apps Script. See
  `docs/WORKSPACE-SMTP-RELAY.md`.
- Event signups accept the renamed **Knock Out Quiz with Steve Perry** title
  while retaining compatibility with the former IQA-prefixed title.
- My Tickets v0.6.0 provides email magic-link retrieval, printable QR tickets,
  mobile check-in, a roster, editable allocated tickets (including a soft
  **delete** row action, `wp_trash_post()`-based), and
  `tn_tickets_attendee_roster()` (a shared ticket-holder list other plugins
  can build attendee pickers from). `preferred_names_for_item()` resolves one
  Preferred Name per seat on multi-quantity ticket orders. A reusable
  **"Ticket Names"** admin screen (WooCommerce menu) edits any order's
  per-seat Preferred Name directly.
- Event Schedule Manager v3.9 adds a team roster picker for team-based event
  signups: an admin "Team Rosters" screen (a team dropdown, grouped by
  event and sorted alphabetically within each event by displayed name, with
  a player count and an editable Team Name field, driving one
  shared picker panel, plus a per-event summary table of Total Teams/Total
  Players/solo-team count), a "Export All Rosters (CSV)" download (including
  a Flight column, added 2026-07-29), a public
  `/team-rosters/` page listing every team event's teams/captains/players
  (no emails, cached via a transient invalidated on roster/status/create
  writes so the page loads quickly), and a captain-facing "Choose Team
  Members" screen on `/manage-signups/` (currently **disabled** — see
  below), so registered ticket holders can be assigned to a team instead
  of typed as free text, with cross-team exclusion per event (scoped per
  base event title, not per flight — confirmed intentional). Deployed and
  hash-verified live. The admin screen, roster contents (including
  allocated tickets), CSV export, the public page, and its caching are all
  verified working live.
- **Team names are derived at display time, never stored** (owner's
  decision, 2026-07-30, implemented in `tn_tde_team_display_name()`): a team
  with no name of its own shows as "Team {captain}", and a team with exactly
  one assigned player is prefixed "FA: ". Any "FA: " already present in the
  stored name is stripped before the rules re-apply, so the prefix tracks
  the live player count instead of the 2026-07-28 one-time rewrite's
  now-stale snapshot, and nothing double-prefixes. The rule is applied by
  the admin dropdown and panel header, the CSV export, and the public
  `/team-rosters/` page; the stored value is what the Team Name field edits
  and what the captain's confirmation email quotes, and is left untouched.
- **2026-07-29: fixed real Team Rosters admin sluggishness and disabled
  captain roster self-service**, both at the owner's explicit request.
  `TN_My_Tickets::attendee_roster()` (My Tickets, now v0.6.1) now caches
  its expensive full-order-rebuild result in a 15-minute transient,
  invalidated on order status changes, allocated-ticket writes, and
  preferred-name edits — the admin screen, CSV export, and the public
  page's cache-builder all benefit since they share this function. The
  captain-facing "Choose Team Members" flow is switched off via a single
  `tn_tde_captain_roster_editing_enabled()` flag (Event Schedule Manager,
  now v3.6) currently returning `false`; the admin Team Rosters screen is
  unaffected and remains the only way to assign team members while this
  is off. Both deployed and hash-verified; **not yet functionally
  verified live** (requires an authenticated admin session or a real
  captain magic-link token — see docs/HANDOFF.md).
- **Live drift discovered 2026-07-29**: production was already running a
  generalized waitlist feature (`tn_tde_signup_is_waitlist_event()`,
  covering Quiz Bowl in addition to "All Trivia: The Gathering") not
  present in `main`, matching an unmerged `agent/quiz-bowl-waitlist`
  remote branch. Reconciled by patching the session's own changes onto
  the live file rather than overwriting it (zero fuzz, confirmed no
  overlap); the waitlist feature itself remains un-merged into `main` —
  see docs/HANDOFF.md's next steps.
- 29 solo (1-player) teams had their team name one-time-prefixed with
  "FA: " (blank names got "FA: {captain name}") via a temporary admin
  action, applied and confirmed live, then removed from source.
- **Site email now runs on Google Workspace** (migrated 2026-07-29):
  `info@trivianationals.org` is a real Workspace mailbox (MX/SPF/DKIM
  configured on `trivianationals.org`'s DNS), and the Event Signups Apps
  Script (`google-apps-script/event-signups/Code.gs`) is deployed under that
  Workspace account natively — no more Gmail send-as alias, no more routing
  through HostGator's SMTP server. Verified end to end via full header
  inspection (DKIM/SPF/DMARC pass, delivered via `gmailapi.google.com`, no
  `wartburg.websitewelcome.com` hop) both directly and through the real
  `/event-signups/` "Email My Signups" flow. `info2@`, `leeann@`, and
  `marketing@` are Workspace aliases on the `info@` user with Gmail filters
  forwarding externally, replicating the old HostGator forwarders. See
  docs/HANDOFF.md and docs/DECISIONS.md for details and remaining gaps.
- On 2026-08-01 the existing Workspace-owned Web App was redeployed as Version
  5 with the tracked `email_quota` action and quota metadata. Timed Quiz
  authenticated successfully: quota reported 97, one authorized test message
  was accepted, and quota reported 96. The owner confirmed inbox delivery and
  that the personalized production link worked; header inspection remains
  pending. Rotate the shared secret across Apps Script, WordPress, and Timed
  Quiz before any real batch because it was surfaced during setup.
- **Announcements plugin (`trivia-nationals-announcements/`) is live at
  v0.4.2** (deployed and hash-verified 2026-07-29; committed and pushed to
  `main` — see docs/HANDOFF.md): a native-CPT admin screen for authoring
  announcements (Title/Teaser/HTML body/Published-Draft), a
  drag-and-drop **"Reorder"** admin screen, a public `/announcements/`
  page headed **"News & Notes"** on-site (newest-first, one combined
  list, no per-announcement permalinks), and a **Send Digest** tool that
  emails selected announcements' full content (plus a News & Notes link)
  to the same filtered/deduplicated audience Attendee Email uses, with a
  ticket-purchase date-range filter, recipient preview, test-send, a
  manual "Send (or resend) to specific addresses" tool, and a resumable
  batched real send. Sends go through the same Apps Script relay as other
  site email, now with per-recipient fallback-path logging and an
  automatic pause if the Apps Script daily email-service quota is hit
  mid-batch (see docs/HANDOFF.md's 2026-07-29 entries for the full
  incident this responded to). CPT/admin/public-page behavior is
  live-verified end to end; the quota-pause path itself is verified by
  code inspection and a raw endpoint test, not by triggering it through a
  real authenticated AJAX batch send.
- Event Schedule Manager also carries a **waitlist feature now generalized
  to Quiz Bowl in addition to "All Trivia: The Gathering"** — signup for
  either event shows a waiting-list form instead of flight selection once
  flights are full. The original TTG-only version was discovered live on
  2026-07-28 and captured in Git; the Quiz Bowl generalization was found
  live again on 2026-07-29 (from the unmerged `agent/quiz-bowl-waitlist`
  branch) and preserved during that day's deploy, but **is still not in
  `main`** — see docs/HANDOFF.md's next steps.
- **5 x 5 signup availability**: newly generated selectable signup options
  use an exact allowlist of Flights D and E. Flights A-C, Semi-Finals, Finals,
  and any other 5 x 5 session labels are excluded. Existing stored signups are
  not modified. Deployed and live-verified 2026-07-30.
- The only valid purchased admission product is exactly **Trivia Nationals 2026
  Ticket**, production WooCommerce product ID `18347`.
- Allocated tickets use the `TN26A-####` number format and participate in ticket
  lookup, QR validation, check-in, and the roster.
- Attendee Email v0.3.0 is installed and active. Its recipient preview returned
  180 unique addresses for the ticket product plus allocated holders at the
  time tested. No attendee blast was sent during installation/testing.
- `scores.trivianationals.org` serves the tracked static coming-soon design.
- Read-only explicit-FTPS access for the scores account was verified on
  2026-07-27.

## Pushed source

- Git branch `main`, remote `origin`.
- Recent feature commits include:
  - `cae6f43` — resilient attendee email dashboard;
  - `9acf897` — renamed Knock Out signup compatibility;
  - `a956937` — electronic ticketing and production integrations.

## Unreleased or unscoped

- The actual scoring dashboard does not exist. Framework, authentication,
  tournament formats, data model, admin workflow, and attendee views remain to
  be designed.
- `wp_mail()` (the fallback when the Apps Script call fails, and the direct
  path for other mail such as WooCommerce order emails) still goes through
  HostGator's local `mail()`, which silently drops messages. Not yet
  migrated to send via Workspace SMTP directly; see docs/HANDOFF.md.
- Unreleased WordPress source removes the unreliable `wp_mail()` fallback from
  Event Schedule Manager, Announcements, and Attendee Email bulk paths and
  prepares them for the shared droplet gateway. Production remains on the old
  plugin versions and Apps Script until the staged rollout is explicitly
  performed. WooCommerce core order mail is intentionally outside this first
  migration.
- Two stray Apps Script Web App deployments from the Workspace migration's
  troubleshooting (owned by the original personal-Gmail-authorized project)
  are still live but unused. Harmless; a cleanup candidate.
- Attendee Email's Gmail relay path has not been exercised from that dashboard
  because doing so would send a real message.
- Confirm production usage before modifying the separate
  `trivia-nationals-event-schedule/` plugin.
- A real 182-recipient Announcements digest sent 2026-07-29 had ~76
  recipients silently fall through to the broken `wp_mail()` fallback
  after the Apps Script relay's daily email-service quota was exhausted;
  the resend to still-undelivered addresses (using the new manual resend
  tool) was deliberately deferred by the user to a later session.
- Whether/when the Apps Script relay's daily email quota ramps up from its
  apparent low initial allowance toward Workspace's documented 1,500/day
  ceiling has not been independently confirmed (e.g. via
  `MailApp.getRemainingDailyQuota()`); the current understanding rests on
  a raw error message and secondary sources, not a direct quota check.

## Local artifacts excluded from Git

The working directory may contain customer/order CSV exports, generated QR
codes, photos, historical snapshots, packaged ZIPs, and secret-bearing Apps
Script working copies. They are not part of the shared project memory.
