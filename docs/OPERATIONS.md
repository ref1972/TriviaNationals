# Operations

## Beginning a work session

1. Pull/fetch the shared Git repository when safe.
2. Read `PROJECT.md`, `docs/CURRENT-STATE.md`, and `docs/HANDOFF.md`.
3. Inspect `git status --short`; do not disturb unrelated local artifacts.
4. Determine whether the relevant source is merely local, pushed, deployed, or
   live verified.

## Ticket administration

- Purchased tickets are derived from eligible paid WooCommerce order items.
- Allocated tickets are managed in the Trivia Nationals WordPress admin menu.
- Staff use `/ticket-check-in/` on a phone to validate a QR code, optionally
  cancel verification without checking in, or record check-in.
- Use the roster admin screen to review checked-in and outstanding attendees.

## Attendee communications

- Use **Trivia Nationals → Email Attendees**.
- Select products and allocated-ticket inclusion deliberately.
- Preview the unique count and source breakdown.
- Use a test message when appropriate, remembering it sends real email.
- Confirm the message is an appropriate attendee communication before starting.
- Keep the browser open during batches; interrupted batches can resume while
  their one-hour transient remains available.
- The tool has no unsubscribe/marketing-consent suppression list.

## Announcements digest

- Use **Announcements → Send Digest** to email selected Published
  announcements' full content to a filtered attendee audience (same
  product/allocated-ticket selection as Email Attendees, plus a
  ticket-purchase date-range filter).
- If a batch send stops early with a message about the Apps Script relay's
  daily quota being reached, it has been paused deliberately, not failed
  silently — use "Resume interrupted send" after the quota resets
  (typically around midnight Pacific Time) to continue from where it
  stopped.
- The Recent Sends log's "Via fallback" column lists any recipients whose
  message actually went out through HostGator's `wp_mail()` rather than
  the Workspace relay — treat those as unconfirmed deliveries and
  cross-check Google Workspace's Email Log Search before assuming they
  arrived.
- Use **Announcements → Send Digest**'s "Send (or resend) to specific
  addresses" tool to resend just to a known list of addresses (e.g. ones
  confirmed undelivered in Email Log Search) without resending to
  everyone else.

## Site email

- `info@trivianationals.org` is a Google Workspace mailbox; manage it and
  its aliases (`info2@`, `leeann@`, `marketing@`) and forwarding filters via
  Google Admin Console (Directory → Users) and that mailbox's own Gmail
  settings, not WordPress.
- WordPress's outbound signup/summary email goes through the Event Signups
  Apps Script (see docs/DEPLOYMENT.md); its endpoint/secret are set on
  **Event Schedule Manager → Signup Settings**.
- `wp_mail()` itself (fallback path, and other WordPress-originated mail)
  still uses HostGator's local `mail()`, which is unreliable — do not trust
  a `wp_mail()` return value of `true` as proof of delivery.

## Checkpointing

Use `scripts/project-checkpoint.sh --write`, review and update the shared docs,
then run `--check`, inspect the diff, commit, and push. See
`docs/CHECKPOINT-PROCESS.md`.
