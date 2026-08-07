# Data model and identifiers

## Purchased tickets

- Eligible WooCommerce product name: `Trivia Nationals 2026 Ticket`.
- Production product ID: `18347`.
- Orders must be paid and not cancelled, refunded, or failed.
- Ticket display includes registered billing name and preferred badge name when
  present.
- Check-in metadata is stored idempotently on the WooCommerce order item,
  including timestamp and staff user ID.

## Allocated tickets

- WordPress custom post type: `tn_alloc_ticket`.
- Number format: `TN26A-####`.
- Editable fields include preferred name, email, amount paid, and note.
- Allocated tickets validate as paid admission and are included in lookup,
  scanning/check-in, roster reporting, and optional attendee-email selection.

## Event signups

- Event Schedule Manager uses the `tn_tde_signup` custom post type and connected
  Google Apps Script/Sheet workflows.
- The **Signups** sheet is the source for attendee-facing "Your Trivia
  Nationals 2026 event signups" email contents. WordPress requests matching
  rows through the Apps Script `event_signup_summary_lookup` action, then adds
  the WordPress-managed secure management link and sends the existing branded
  email through the relay.
- The current Knock Out event matcher supports both `Knock Out Quiz with Steve
  Perry` and the former `IQA Knock Out Quiz with Steve Perry`.

## Attendee email selection

- Published parent WooCommerce products are selectable.
- Eligible orders are paid and not cancelled/refunded/failed.
- Product variations roll up to their parent product.
- Recipients are deduplicated by lowercased billing email across selected
  products and allocated tickets.
- `{first_name}` resolves from preferred line-item name, billing first name, or
  the fallback `there`.

Avoid placing real attendee records or exports in this document.
