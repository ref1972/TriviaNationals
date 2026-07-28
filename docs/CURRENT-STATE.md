# Current state

Last human review: 2026-07-27.

## Live and verified

- The primary site is WordPress/WooCommerce at `trivianationals.org`.
- Event signups accept the renamed **Knock Out Quiz with Steve Perry** title
  while retaining compatibility with the former IQA-prefixed title.
- My Tickets v0.5.8 provides email magic-link retrieval, printable QR tickets,
  mobile check-in, a roster, editable allocated tickets, and
  `tn_tickets_attendee_roster()` (a shared ticket-holder list other plugins
  can build attendee pickers from). `preferred_names_for_item()` now
  resolves one Preferred Name per seat on multi-quantity ticket orders
  (was previously one shared name per line item) — confirmed live against
  a real order.
- Event Schedule Manager v2.4 adds a team roster picker for team-based event
  signups: an admin "Team Rosters" screen (a team dropdown, grouped by
  event with a player count and an editable Team Name field, driving one
  shared picker panel), a "Export All Rosters (CSV)" download, a public
  `/team-rosters/` page listing every team event's teams/captains/players
  (no emails), and a captain-facing "Choose Team Members" screen on
  `/manage-signups/`, so registered ticket holders can be assigned to a
  team instead of typed as free text, with cross-team exclusion per event
  (scoped per base event title, not per flight — confirmed intentional).
  Deployed and hash-verified live on 2026-07-28. The admin screen, roster
  contents (including allocated tickets), CSV export, and the public page
  are all verified working live; the captain-facing "Choose Team Members"
  flow is not yet live-tested end to end (see docs/HANDOFF.md).
- 29 solo (1-player) teams had their team name one-time-prefixed with
  "FA: " (blank names got "FA: {captain name}") via a temporary admin
  action, applied and confirmed live 2026-07-28, then removed from source.
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
- The repository's Event Signups Apps Script now reads `SYNC_SECRET` from Script
  Properties, but that source change is not deployed. The formerly embedded
  secret remains in Git history and should be rotated in both Apps Script and
  WordPress when production is migrated to Script Properties.
- Attendee Email's Gmail relay path has not been exercised from that dashboard
  because doing so would send a real message.
- Confirm production usage before modifying the separate
  `trivia-nationals-event-schedule/` plugin.

## Local artifacts excluded from Git

The working directory may contain customer/order CSV exports, generated QR
codes, photos, historical snapshots, packaged ZIPs, and secret-bearing Apps
Script working copies. They are not part of the shared project memory.
