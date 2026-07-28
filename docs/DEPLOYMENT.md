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

## Rollback

- WordPress: retain the last known-good plugin ZIP/source and confirm the
  recovery-mode/admin path remains available.
- Scoped Event Schedule Manager deployment: use the ignored timestamped backup
  created by `scripts/wp-plugin-ftps.sh`.
- Scores placeholder: restore the prior tracked `index.html` from Git and upload
  that single file.
