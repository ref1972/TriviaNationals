# Decisions

## 2026-08-01 — Timed Quiz moved to a standalone repository

The reusable quiz application and its path history moved to
[ref1972/timedquiz](https://github.com/ref1972/timedquiz). This repository keeps
the shared Apps Script relay source because it also serves existing Trivia
Nationals production systems, plus a pointer to the standalone project.

Reason: Timed Quiz has its own runtime, database, domain, deployment, and
release cycle. Keeping duplicate application source in both repositories would
create an immediate source-of-truth and deployment-drift risk.

## 2026-08-01 — Host Pop Culture Bee beside CASS at `bee.triviaworkshop.com`

The production target is the existing CASS DigitalOcean droplet, using a
separate nginx virtual host at `https://bee.triviaworkshop.com`. The quiz will
have its own localhost port, service, persistent data directory, backups, and
side-by-side Node 24 runtime; CASS stays on its current Node 20/PM2 processes.

Reason: the droplet is already stable, lightly loaded, and equipped with
nginx/Certbot. Reusing it avoids provisioning another host for a small,
short-lived ~70-player workload while preserving operational isolation from
CASS.

## 2026-07-29 — Never spend the quota-exhausted recipient on the fallback mailer

A Codex code review of `32579cb` found that the v0.4.1 quota-pause logic
still attempted `wp_mail()` and advanced the batch offset for the
recipient whose Apps Script call actually hit the quota, so that
recipient was marked "done" and silently skipped on every future resume —
even though the fallback might have dropped their message the same way it
dropped ~76 others in the incident this feature was built to prevent.
Fixed in v0.4.2: `send_to()` no longer attempts `wp_mail()` once quota
exhaustion is detected, and `ajax_send_batch()` checks `quota_exhausted`
before incrementing the offset or saving the transient.

Reason: once the quota is confirmed exhausted, every subsequent send will
fail identically, so there's nothing to gain by gambling one more
recipient on a fallback mailer already known to silently lose mail — and
every recipient a paused batch skips past is one that "Resume interrupted
send" will never retry.

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
## 2026-07-31 — Pop Culture Bee uses one Node/SQLite instance and Workspace-only invitation delivery

The launch candidate (now maintained in `ref1972/timedquiz`) is a consolidated
Node 24, Express, and SQLite application. Production must run one always-on instance
with persistent storage and HTTPS. Invitation links are hashed for lookup and
encrypted separately for controlled resend/rotation. Invitation email goes
directly through the existing Google Workspace Apps Script relay in small,
quota-preflighted batches and has no `wp_mail()` fallback.

Reason: roughly 70 players do not justify distributed infrastructure, while a
single persistent SQLite database is easy to audit and back up. The confirmed
HostGator fallback can report success while dropping mail, so stopping safely
is more important than finishing a batch through an untrusted path.

## 2026-07-31 — Pop Culture Bee ties use total server-measured correct-answer time

Rank by score descending, then the sum of `elapsed_ms` for scored-correct
answers ascending. Each elapsed value is measured from server `served_at` to
server finalization and capped at the visible question window, so the hidden
transport grace does not add tiebreak time. Email is only a final deterministic
ordering if both score and correct-answer time are identical.

Reason: this implements the recorded design requirement and makes the top-N
cut reproducible without trusting a player's clock.
