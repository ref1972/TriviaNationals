# Deployment

## Main WordPress site

- Site: `https://trivianationals.org`
- Hosting family: HostGator/cPanel shared hosting.
- Preferred FTPS host: `wartburg.websitewelcome.com:21`, explicit FTPS.
- Main scoped deploy account: `tndeploy@trivianationals.org`.
- Credential location: macOS Keychain, service `ftp.trivianationals.org`.
- `scripts/wp-plugin-ftps.sh` safely pulls, diffs, and deploys only
  `trivia-desc-editor-restored/trivia-desc-editor.php`. It verifies a reviewed
  live hash and creates an ignored local backup before upload.
- Other WordPress plugins have been deployed through versioned ZIP upload in
  WordPress when FTP was unreliable. Confirm current live version and take a
  recoverable backup before replacement.

### Tracked production plugins and their remote paths

The FTPS account opens in WordPress's `wp-content` directory, so remote paths
are relative to it:

| Plugin | Remote path | Deploy helper |
| --- | --- | --- |
| Event Schedule Manager | `plugins/trivia-desc-editor-restored/trivia-desc-editor.php` | `scripts/wp-plugin-ftps.sh` |
| Team Roster Google Sheets Sync | `plugins/trivia-nationals-team-roster-sheets/trivia-nationals-team-roster-sheets.php` | none — manual, see below |

`scripts/wp-plugin-ftps.sh` is hardcoded to the Event Schedule Manager and
**does not** handle the roster sync plugin. Deploying that one is manual;
follow the same discipline the script enforces — fetch the live file, diff it,
back it up, upload, then re-fetch and compare checksums. Verified 2026-08-05:
the tracked copy is byte-identical to the live file
(`56da6b48ee70a44540ae8e638e155783c5d0acba5cdaa412bc74d573961445fe`).

The roster sync's Apps Script counterpart is
`google-apps-script/team-roster/Code.gs`, deployed by hand in the Apps Script
editor. Its shared secret lives in Script Properties as `ROSTER_SHARED_SECRET`
and must never be committed.
- The `tndeploy` account can also reach other plugins directly as plain files
  under `plugins/<slug>/`, confirmed 2026-07-28 for
  `plugins/trivia-nationals-my-tickets/trivia-nationals-my-tickets.php` — no
  ZIP needed, same fetch/diff/backup/upload/verify discipline as the scoped
  script, just without a dedicated script yet.
- This host's FTPS **data channel has intermittently failed on TLS 1.3**
  with `451 Error during read from data connection` — seen 2026-07-28,
  *after* a full 55KB file had already been sent, which briefly left the
  live file empty until re-uploaded. Force `--tlsv1.2 --tls-max 1.2` on both
  the fetch and the upload `curl` calls; `wp-plugin-ftps.sh` does this by
  default now. Always keep a fetched pre-upload backup and verify the
  post-upload hash — don't trust a `curl -T` exit code alone after a `451`.

Never broaden the scoped main-site FTP script without reviewing its remote root
and rollback behavior.

## Scores placeholder

- Public site: `https://scores.trivianationals.org`
- Source: `scores-site/index.html`
- Account: `scoreftp@scores.trivianationals.org`
- Credential location: macOS Keychain, account above and service
  `ftp.trivianationals.org`.
- Connect host: `wartburg.websitewelcome.com:21`, explicit FTPS.
- Remote target: account-root `/index.html`.
- Current deployment is a single-file overwrite with no build step.

Example shape (retrieve the password into a permission-600 temporary netrc;
never put it in argv, history, or Git):

```text
curl --ssl-reqd --netrc-file <temporary-netrc> \
  -T scores-site/index.html \
  ftp://wartburg.websitewelcome.com:21/index.html
```

After upload, fetch or open the public URL and verify the rendered page. The
scores account and main site share underlying hosting ownership but have
different FTP roots; do not assume their public DNS IPs identify separate
infrastructure.

## Event Signups Apps Script

- Source: `google-apps-script/event-signups/Code.gs`.
- Runs as a Web App deployed under the `info@trivianationals.org` Google
  Workspace account (not a personal Gmail account) — this is what lets
  `GmailApp.sendEmail()` send natively as `info@` with no send-as alias.
- `SYNC_SECRET` lives in that project's Script Properties (Apps Script
  editor → gear icon → Script Properties), not in source.
- To redeploy after a code change: paste the updated `Code.gs` into the
  project (logged in as `info@trivianationals.org`), save, then **Deploy →
  Manage deployments** → edit the existing Web App deployment → **Version:
  New Version** → Deploy. This keeps the same Web App URL, so WordPress's
  **Event Schedule Manager → Signup Settings** endpoint setting does not
  need to change.
- Do not try to deploy this project from a different Google account via
  Drive sharing — Apps Script refuses "New deployment" for a non-owner even
  with Editor access. If the script ever needs to move to a different
  account again, create a fresh project owned directly by that account and
  copy the code in, rather than sharing the existing one.
- WordPress's endpoint URL and shared secret are set via **Event Schedule
  Manager → Signup Settings** (`tn_tde_signup_sheets_endpoint` /
  `tn_tde_signup_sheets_secret` options).

## Shared Workspace Gmail API gateway

The replacement for Apps Script bulk-mail delivery is under
`workspace-mail-relay/`; the architecture and staged owner-assisted rollout
are in `docs/WORKSPACE-SMTP-RELAY.md`. Trivia Nationals remains on HostGator.
Only the gateway runs as an isolated service on the existing droplet.
Gateway rc2 is deployed at `https://mail.triviaworkshop.com`; its isolated
service, TLS, OAuth readiness, authentication rejection, rolling capacity,
daily backup timer, Timed Quiz health, and CASS health are verified. Timed Quiz
rc23 is switched to its app-specific gateway credential. On 2026-08-01, Gmail
API accepted exactly one owner-authorized test invitation (HTTP 200 and a
provider message ID), and the owner confirmed inbox delivery. Personalized-link
and header verification remain pending. WordPress is not switched and still
uses Apps Script. The Internal Gmail API
OAuth client is authorized as `info@` for only `gmail.send`, and caller secrets
exist outside Git. Do not switch WordPress until the personalized link and
headers are verified. This migration does not yet cover WooCommerce core order
emails.

## Rollback

- WordPress: retain the last known-good plugin ZIP/source and confirm the
  recovery-mode/admin path remains available.
- Scoped Event Schedule Manager deployment: use the ignored timestamped backup
  created by `scripts/wp-plugin-ftps.sh`.
- Scores placeholder: restore the prior tracked `index.html` from Git and upload
  that single file.

## Pop Culture Bee quiz

- Source and detailed deployment record moved to
  [ref1972/timedquiz](https://github.com/ref1972/timedquiz). Local checkout:
  `/Users/russellefriedewald/Documents/Projects/TimedQuiz`.
- Selected public URL: `https://bee.triviaworkshop.com`.
- Selected host: the existing CASS DigitalOcean droplet. Keep the quiz
  isolated from CASS with separate code, data, and backup directories, a
  dedicated localhost port and service, and its own nginx virtual host and
  certificate.
- The droplet currently runs Node 20 for CASS and does not have Docker. The
  quiz requires Node 24 for `node:sqlite`; install Node 24 side by side and do
  not replace CASS's runtime.
- Runtime: one Node 24 process and one persistent SQLite database.
- Never run more than one application instance against this SQLite database.
- Production configuration, preflight, backup, verification, and rollback
  instructions now live in the Timed Quiz repository.
- Verify `/health`, the release identifier, one test invitation, mobile timing,
  admin review, restart, and result ordering before sending real invitations.
- Workspace email requires redeploying
  `google-apps-script/event-signups/Code.gs`, then configuring
  `EMAIL_RELAY_URL`/`EMAIL_RELAY_SECRET`. The quiz sender intentionally has no
  HostGator/`wp_mail()` fallback.
- Production rc23 is deployed at `https://bee.triviaworkshop.com` with DNS,
  TLS, an isolated service/database/backups, and production secrets outside
  Git. Detailed operations live in the Timed Quiz repository.
