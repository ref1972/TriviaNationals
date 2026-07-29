# Decisions

## 2026-07-29 — Pause a digest batch send on Apps Script quota exhaustion
instead of letting it silently fall back for every remaining recipient

`TN_Announcements::ajax_send_batch()` now stops immediately (via
`wp_send_json_error()`, leaving the batch transient at its correct offset
for "Resume interrupted send") the moment the Apps Script relay reports its
daily email-service quota is exhausted, rather than continuing to send the
rest of the batch through `wp_mail()`.

Reason: a real send confirmed `wp_mail()` returns `true` on this host even
when a message is silently dropped, so continuing past a confirmed quota
hit would report false successes for every remaining recipient — exactly
what happened before this fix, when ~76 of 182 recipients "succeeded" on
screen without ever being delivered by either path. Pausing and surfacing a
clear resume-later message is safer than a fast, misleading finish.

## 2026-07-29 — Build a manual "send/resend to specific addresses" tool
rather than an automatic retry

Added to the Announcements plugin (v0.3.0) instead of having failed sends
retry themselves.

Reason: the user explicitly wanted to control the timing of any resend
("I will wait until tomorrow to retry") rather than have it happen
automatically, given the sending domain was already under investigation
for throttling; a manual, address-scoped tool also avoids resending to
recipients who already received the message successfully.

## 2026-07-29 — Track and surface per-recipient delivery path
(`apps_script` vs `wp_mail_fallback`) in the Announcements send log

`send_to()` returns which path an individual send actually took, and the
Recent Sends admin log now lists the specific fallback addresses per send
event.

Reason: the shared `tn_tde_send_signup_email()` wrapper's boolean return
conflates "reliably sent via the Workspace relay" with "silently vanished
into HostGator's `wp_mail()`" — both currently report `true`. Per-recipient
path visibility lets an admin tell the difference and know exactly whom to
follow up with, without needing to cross-reference external mail logs
every time.

## 2026-07-29 — New `tn_announcement` CPT uses native post fields, not
custom postmeta

Unlike `tn_tde_signup`/`tn_alloc_ticket` (bare CPT shells + custom
postmeta), `tn_announcement` uses native `post_title`/`post_content`/
`post_excerpt`/`post_status` directly.

Reason: title/body/teaser/published-or-draft map exactly onto WordPress's
own post fields, so the standard post editor (with working image upload)
is the entire authoring UI for free — custom postmeta and a custom admin
screen would only reproduce what core already provides.

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
