# Trivia Nationals My Tickets

Passwordless electronic tickets backed directly by WooCommerce orders.

## Current MVP

- Creates a **My Tickets** page containing `[tn_my_tickets]` on activation.
- Sends 30-minute magic links without revealing whether an email exists.
- Uses the Event Signups Gmail relay and branded Trivia Nationals fallback headers.
- Shows one printable ticket per eligible line-item quantity.
- Shows both the registered billing name and preferred badge name.
- Requires an order to be paid and not cancelled, refunded, or failed.
- Uses the line-item preferred badge name when available.
- Rate-limits requests by email and IP address.
- Requires the exact product name `Trivia Nationals 2026 Ticket` and lets administrators select its WooCommerce product ID.
- Adds a signed QR code to every admission ticket.
- Requires a WordPress user with `manage_woocommerce` permission to validate and check in a ticket.
- Stores an idempotent timestamp and staff user ID on the WooCommerce order item at check-in.

The production product ID is `18347`. Both that configured ID and the exact product name must match before an order item is eligible.

## Install and configure

1. Upload the `trivia-nationals-my-tickets` folder to `wp-content/plugins/`.
2. Activate **Trivia Nationals My Tickets** after WooCommerce is active.
3. Open **WooCommerce > TN Tickets**.
4. Confirm the My Tickets page, product ID `18347`, and event dates `August 7–9, 2026`.
5. Confirm WordPress transactional email delivery before public launch.

## Deliberately deferred

- Attendee-name assignment for multi-quantity purchases
- Event-signup integration
- Persistent attendee profiles
- HTML email and wallet passes

## QR library

QR rendering uses the vendored, dependency-free QRCode.js library by davidshimjs under the MIT License. See `assets/qrcode.LICENSE`.
