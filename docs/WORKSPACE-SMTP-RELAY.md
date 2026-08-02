# Shared Google Workspace SMTP relay

Status: source implementation in progress; not deployed or configured. No mail
has been sent through it.

## Architecture

The Trivia Nationals WordPress/WooCommerce site remains on HostGator. Pop
Culture Bee remains on the DigitalOcean droplet. A small third service on that
droplet provides an authenticated HTTPS mail gateway:

```text
Timed Quiz (droplet) ─┐
                      ├─ authenticated HTTPS → local relay service
WordPress (HostGator) ┘                          │
                                                └─ STARTTLS SMTP
                                                   smtp-relay.gmail.com
```

Only the relay process and its audit database live on the droplet. No
WordPress, WooCommerce, orders, tickets, attendee lists, or site database move
there. Workspace allowlists only the droplet's fixed public IPv4 address, not
HostGator's shared outbound infrastructure.

## Source and isolation

- Source: `workspace-mail-relay/` in this repository.
- Intended hostname: `mail.triviaworkshop.com`.
- Listener: `127.0.0.1:8082`, never a public raw Node port.
- Public ingress: nginx HTTPS, exact `/health` and `/v1/mail` locations only.
- Service user/data: `trivia-mail-relay` and
  `/var/lib/trivia-mail-relay/audit.sqlite`.
- Timed Quiz and CASS services, runtimes, ports, data, and processes remain
  separate.
- Google Workspace is configured to accept relay SMTP only from the confirmed
  droplet public IP and to require TLS.

## Security and delivery rules

- Each caller has its own client ID and long random bearer secret. The service
  stores only SHA-256 hashes of client secrets.
- The sender and Reply-To are server-side configuration; callers cannot choose
  arbitrary From addresses.
- One recipient is accepted per request and per SMTP transaction.
- A single global queue serializes all callers and spaces accepted messages by
  at least three seconds, so simultaneous Timed Quiz and WordPress activity
  cannot multiply the sending rate.
- Subject and body sizes are bounded; request size and nginx request rate are
  bounded.
- Recipient addresses are SHA-256 hashed in the local audit database. Message
  bodies and personalized links are never logged.
- A message counts as sent only after Google's SMTP server returns acceptance
  after DATA.
- There is no SMTP, Apps Script, `wp_mail()`, or other automatic fallback.
- Any rejection or ambiguous transport failure pauses the caller's batch with
  that recipient still pending.

The gateway reports remaining capacity under configurable rolling one-hour and
24-hour
application safety limit. It does not claim to know Google's account-wide
remaining quota. The proposed initial shared policy is one message every three
seconds, at most 300 accepted messages per rolling hour, and at most 1,000 per
rolling 24 hours. This is sufficient for Timed Quiz plus current Trivia
Nationals audiences while remaining deliberately below Workspace SMTP relay's
documented limits. The owner can choose different limits before deployment.

## Callers

### Timed Quiz

Timed Quiz keeps its existing relay abstraction and adds app-specific header
authentication when `EMAIL_RELAY_CLIENT_ID` is set. Planned production values:

```text
EMAIL_RELAY_URL=https://mail.triviaworkshop.com/v1/mail
EMAIL_RELAY_CLIENT_ID=timed_quiz
EMAIL_RELAY_SECRET=<unique secret outside Git>
```

Its test send, five-recipient batches, exact player identity, sent markers,
pause/resume behavior, and no-fallback rule remain in the application.

### Trivia Nationals WordPress

Event Schedule Manager owns the shared WordPress request helper because it
already stores the relay endpoint and secret. During migration it sends both
the new headers and the old body secret, allowing the same source to call the
current Apps Script endpoint before cutover. Planned endpoint:

```text
https://mail.triviaworkshop.com/v1/mail
```

The client ID is `trivia_nationals`; its secret must differ from Timed Quiz.
Announcements and Attendee Email use the shared helper. Their bulk loops pause
without advancing the current recipient on any relay failure. HostGator
`wp_mail()` fallback is removed from these paths because it has already
reported false success and lost mail.

The initial migration covers:

- Event Signup summary/confirmation paths that already use the shared helper;
- Announcements digest test, batch, and manual resend paths;
- Attendee Email test and batch paths; and
- Timed Quiz invitation test and batch paths.

WooCommerce core order emails are a separate path. Do not route all `wp_mail()`
globally through this service in the first rollout; that needs independent
template, failure, volume, and compatibility testing.

## Remaining implementation checks

1. Finish PHP syntax/static checks for all three changed plugins.
2. Finish Timed Quiz tests and documentation for app-specific authentication.
3. Add deployment/provisioning automation with backup, health, service, nginx,
   and rollback verification.
4. Test gateway auth, safety capacity, TLS failure, SMTP 4xx/5xx, timeout, and
   successful acceptance without contacting real recipients.
5. Inspect WordPress batch state carefully to ensure every failure leaves the
   current recipient pending.

## Owner-assisted setup and staged rollout

No step below authorizes a real invitation or attendee send.

1. Owner creates DNS `A` record `mail.triviaworkshop.com` → confirmed droplet
   IPv4.
2. In Google Admin → Gmail → Routing → SMTP relay service:
   - allow only registered Workspace senders (or the narrowest option that
     permits `info@trivianationals.org`);
   - authenticate only the confirmed droplet IPv4;
   - require TLS.
3. Codex provisions the isolated service, audit backup, nginx virtual host, and
   TLS certificate without changing any caller endpoint.
4. Codex verifies unauthenticated requests fail, readiness succeeds, audit is
   empty, Timed Quiz/CASS remain healthy, and WordPress is unchanged.
5. Generate two independent secrets, store only their hashes at the relay, put
   raw values only in the respective caller configuration, and never display
   them in documentation or logs.
6. Switch Timed Quiz first, restart only its service, run capacity check, and
   send one explicitly authorized owner test. Verify link identity, headers,
   SPF/DKIM/DMARC, and Workspace Email Log Search.
7. Switch WordPress's existing endpoint/secret settings. Send one explicitly
   authorized admin test from Attendee Email and one from Announcements; verify
   headers and Email Log Search. Do not start a real batch.
8. After both applications pass, perform deliberately authorized real sends in
   small waves with reconciliation after each wave. Start with five, inspect
   Workspace logs and delivery, then increase to ten; do not run the whole
   audience merely because the configured ceiling permits it.

## Rollback

- Relay service fault before real mail: restore each caller's former Apps
  Script endpoint/secret and stop the isolated relay service.
- Code fault: deploy the prior plugin/release while retaining current
  databases and sent markers.
- After partial acceptance: never restore an older database or skip recipients.
  Reconcile Workspace Email Log Search, retain accepted markers, and resume
  from the first confirmed-unsent recipient.
- An SMTP connection loss after DATA is ambiguous. Pause, inspect Email Log
  Search for that exact recipient/timestamp, and make a manual resend decision.

## Production verification record

When deployed, record separately:

- source commit/tag pushed;
- DNS and TLS verified;
- service release and health;
- Google relay policy and observed droplet IP (never secrets);
- authorized test recipients and owner-confirmed results without committing
  addresses or links;
- SPF/DKIM/DMARC and Email Log Search outcome;
- caller endpoint cutovers;
- CASS, Timed Quiz, WordPress, and WooCommerce health;
- exact real-send authorization and reconciliation, if/when performed.
