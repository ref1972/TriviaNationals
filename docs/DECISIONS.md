# Decisions

## 2026-07-29 — Migrate site email to Google Workspace

`info@trivianationals.org` is now a real Google Workspace mailbox (MX/SPF/DKIM
configured), and the Event Signups Apps Script sends natively as that account
instead of using a personal Gmail account's send-as alias.

Reason: the alias approach still routed every message through HostGator's own
SMTP server as the alias's delivery method (confirmed via a real bounce), so
it never actually escaped HostGator's deliverability problems; it was also
capped at a personal account's 100/day sending quota. Native Workspace
sending removes both the HostGator dependency and the quota ceiling (raised
to 1,500/day), ahead of an expected run of bulk attendee emails.

## 2026-07-27 — Git-backed shared project memory

Claude and Codex share durable knowledge through ordinary Markdown files in
this repository. Tool-specific instruction files point to the same documents.
Conversation memory is helpful but is not authoritative.

Reason: Git provides reviewable history, works across computers, and avoids
locking operational knowledge inside one AI product.

## 2026-07-27 — Separate generated inventory from reviewed context

The checkpoint script records mechanical facts in `docs/inventory/`; agents
maintain the stable architecture, current state, deployment, operations, and
handoff documents.

Reason: automated listings are reproducible, while production status and risk
require human/agent judgment.

## 2026-07-27 — Keep operational secrets and attendee data out of Git

Credentials are referenced only by storage location. WooCommerce exports,
attendee records, generated codes, photos, backups, snapshots, and local
settings remain ignored.

Reason: the shared repository should be portable without becoming a sensitive
data store.

## Existing product decisions

- Ticket eligibility requires the exact product name `Trivia Nationals 2026
  Ticket` and configured product ID `18347`.
- Ticket access uses expiring passwordless links rather than permanent attendee
  accounts.
- Check-in is a mobile-friendly staff workflow backed by signed ticket QR codes.
- Allocated tickets use the distinct `TN26A-####` namespace.
- Attendee email selection is product-based, deduplicated, and deliberately
  gated by preview and confirmation.
- The future scores application is not assumed to be WordPress; its technology
  choice remains open.
