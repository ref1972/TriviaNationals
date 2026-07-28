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

## Checkpointing

Use `scripts/project-checkpoint.sh --write`, review and update the shared docs,
then run `--check`, inspect the diff, commit, and push. See
`docs/CHECKPOINT-PROCESS.md`.
