# Trivia Nationals plugin deployment

The production WordPress plugin is:

```text
plugins/trivia-desc-editor-restored/trivia-desc-editor.php
```

The FTPS account opens in WordPress's `wp-content` directory.

## Connection

- Protocol: explicit FTPS
- Host: `wartburg.websitewelcome.com`
- Port: `21`
- Username: `tndeploy@trivianationals.org`
- macOS Keychain service: `Trivia Nationals FTPS`

Never commit the password, place it in a command, or disable TLS certificate
verification. The public alias `ftp.trivianationals.org` does not match the
server certificate; use `wartburg.websitewelcome.com`.

## Safe workflow

1. Download the production file to a temporary path.
2. Preserve a dated copy of both the local and remote versions.
3. Compare production, local, and GitHub before editing.
4. Treat production as authoritative when it contains newer uncommitted work.
5. Run `php -l` and inspect the Git diff.
6. Upload the verified file over explicit FTPS.
7. Verify the affected public pages with a cache-busting query parameter.
8. Commit the synchronized source to GitHub.

The deployment account's password is stored locally in macOS Keychain, not in
this repository.
