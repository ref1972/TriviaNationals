# Trivia Nationals 2026

This repository contains the custom software and operational documentation for
[trivianationals.org](https://trivianationals.org) and the future scoring
dashboard at [scores.trivianationals.org](https://scores.trivianationals.org).
It is also the durable project memory shared by Claude and Codex.

## Production systems

| Component | Source | Production role |
|---|---|---|
| Event Schedule Manager | `trivia-desc-editor-restored/` | Main site schedule, event descriptions, event signups, homepage integrations, FAQ/admin tools, and Gmail relay integration |
| My Tickets | `trivia-nationals-my-tickets/` | Passwordless electronic tickets, printable QR tickets, mobile staff check-in, roster, and allocated tickets |
| Attendee Email | `trivia-nationals-attendee-email/` | Admin-selected, deduplicated attendee communications based on paid WooCommerce products and allocated tickets |
| Announcements | `trivia-nationals-announcements/` | Admin-authored announcements (native WP CPT with Title/Teaser/HTML body/Published-Draft status), a public `/announcements/` list page (headed "News & Notes" on-site, drag-and-drop orderable via a "Reorder" admin screen), and a "Send Digest" tool that emails selected announcements' full content — plus a link back to the News & Notes page — to a filtered, deduplicated attendee audience. Digest sends go through the same Apps Script relay as other site email, falling back to `wp_mail()` only on relay failure, with per-recipient fallback tracking and automatic pause on Apps Script daily-quota exhaustion (see docs/HANDOFF.md) |
| Event Schedule | `trivia-nationals-event-schedule/` | Separate schedule plugin retained in the repository; confirm live usage before changing or deploying |
| WooCommerce Google Sheets Sync | `woocommerce-google-sheets-sync/` | Synchronizes WooCommerce order information with Google Sheets |
| Event Signups Apps Script | `google-apps-script/event-signups/Code.gs` | Google-side integration for event signup data |
| Scores placeholder | `scores-site/index.html` | Static coming-soon page for the future scoring dashboard |
| Timed Quiz / Pop Culture Bee | [ref1972/timedquiz](https://github.com/ref1972/timedquiz) | Standalone project extracted from this repository; TriviaNationals retains only the shared Workspace Apps Script integration |

## Key public/admin surfaces

- `/event-signups/` — attendee event signup page.
- `/my-tickets/` — passwordless ticket retrieval.
- `/ticket-check-in/` — mobile-friendly staff QR validation and check-in.
- `/announcements/` — public list of all Published announcements, headed
  "News & Notes" on-site, in admin-controlled drag-and-drop order.
- WordPress **Trivia Nationals → Email Attendees** — attendee email dashboard.
- WordPress **Announcements** (own top-level menu) — native Add/Edit screens
  for announcement content and status, plus **Send Digest** and **Reorder**
  submenu pages.
- WordPress Trivia Nationals ticket admin screens — ticket configuration,
  allocated ticket management, and check-in roster.
- `scores.trivianationals.org` — static placeholder only; the real scoring
  application is not yet designed.

## Operating principles

- Git and the files in this repository are the cross-tool, cross-computer source
  of truth.
- Production state, repository state, and conversational memory are separate;
  verify which one a statement describes.
- Production credentials live in macOS Keychain or host-managed configuration,
  never in Git.
- Customer/order exports, attendee data, generated codes, photos, backups, and
  temporary snapshots are local artifacts and are ignored.
- Pull before beginning work when safe, and push reviewed documentation and
  code so the MacBook and other agents receive the same context.

See `docs/CURRENT-STATE.md` for current deployment status and
`docs/CHECKPOINT-PROCESS.md` for the shared checkpoint workflow.
