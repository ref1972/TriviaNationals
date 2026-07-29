# Current state

Last human review: 2026-07-29.

## Live and verified

- The primary site is WordPress/WooCommerce at `trivianationals.org`.
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
- Event Schedule Manager v3.3 adds a team roster picker for team-based event
  signups: an admin "Team Rosters" screen (a team dropdown, grouped by
  event with a player count and an editable Team Name field, driving one
  shared picker panel, plus a per-event summary table of Total Teams/Total
  Players/solo-team count), a "Export All Rosters (CSV)" download, a public
  `/team-rosters/` page listing every team event's teams/captains/players
  (no emails, cached via a transient invalidated on roster/status/create
  writes so the page loads quickly), and a captain-facing "Choose Team
  Members" screen on `/manage-signups/`, so registered ticket holders can be
  assigned to a team instead of typed as free text, with cross-team
  exclusion per event (scoped per base event title, not per flight —
  confirmed intentional). Deployed and hash-verified live. The admin screen,
  roster contents (including allocated tickets), CSV export, the public
  page, and its caching are all verified working live; the captain-facing
  "Choose Team Members" flow is not yet live-tested end to end (see
  docs/HANDOFF.md).
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
- Event Schedule Manager also carries a production-only **"All Trivia: The
  Gathering" waitlist feature** (discovered live on 2026-07-28, now captured
  in Git for the first time) — signup for that event shows a waiting-list
  form instead of flight selection once flights are full.
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
- Two stray Apps Script Web App deployments from the Workspace migration's
  troubleshooting (owned by the original personal-Gmail-authorized project)
  are still live but unused. Harmless; a cleanup candidate.
- Attendee Email's Gmail relay path has not been exercised from that dashboard
  because doing so would send a real message.
- Confirm production usage before modifying the separate
  `trivia-nationals-event-schedule/` plugin.

## Local artifacts excluded from Git

The working directory may contain customer/order CSV exports, generated QR
codes, photos, historical snapshots, packaged ZIPs, and secret-bearing Apps
Script working copies. They are not part of the shared project memory.
