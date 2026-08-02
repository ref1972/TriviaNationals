# Trivia Workshop mail relay

Private application relay for sending approved Trivia Workshop/Trivia Nationals
mail through the Google Workspace Gmail API. It binds only to localhost; nginx is
the authenticated HTTPS entry point for remote WordPress clients.

This source does not contain credentials. Client requests use an app-specific
ID and bearer secret; the service stores only SHA-256 hashes of those secrets.
Recipient addresses are hashed in the local audit database, and message bodies
and personalized links are never written to the audit log.

The `email_quota` response is deliberately a rolling application safety limit,
not a claim about Google's account-wide remaining quota. Every personalized
message is sent as a single-recipient Gmail API request. A global queue paces
all callers and enforces rolling hourly and 24-hour safety ceilings. No
fallback transport exists.

See `docs/WORKSPACE-SMTP-RELAY.md` in the repository root for deployment and
rollout instructions.
