# Trivia Nationals Attendee Email

WordPress admin tool for sending one-off attendee updates to unique email
addresses associated with selected paid WooCommerce products and, optionally,
allocated tickets.

## Admin screen

Open **Trivia Nationals → Email Attendees**. The screen is restricted to users
with the `manage_woocommerce` capability.

1. Compose a subject and HTML message.
2. Select one or more parent WooCommerce products.
3. Choose whether to include allocated ticket holders.
4. Check the unique-recipient preview and its source breakdown.
5. Send a test to the current administrator if desired.
6. Confirm the communication is appropriate before starting the attendee send.

`{first_name}` is supported in both the subject and body.

## Delivery and safety

- Only paid, non-cancelled, non-refunded orders are eligible.
- Recipient addresses are deduplicated case-insensitively across all sources.
- Emails use `tn_tde_send_signup_email()` when available and fall back to
  branded `wp_mail()`.
- The server owns the batch offset and saves progress after every recipient.
- Interrupted browser sessions can resume while the one-hour batch transient
  remains available.
- Recent completed sends are logged, capped at 20 entries.

This tool does not maintain an unsubscribe or marketing-consent list. Use it for
appropriate attendee/event communications, and apply any required suppression
process before sending promotional messages.
