# Sales & Point of Sale (POS)

## Setup

Before taking sales, configure the building blocks under **Sales Setup**:
- **Order Types** — e.g. dine-in, takeaway, delivery.
- **Payment Methods** — cash, card, bank transfer, store credit, etc.
- **Order Sources** — POS, website, mobile app.
- **Sale Types** — pricing tiers (e.g. retail vs. wholesale).
- **Discounts** — a simple named percentage or fixed-amount rate the cashier
  picks manually from a dropdown at the order level. No code, no conditions —
  just a quick, always-available markdown.
- **Vouchers** — the full promotional rule engine, entered/selected by code in
  POS. See **Vouchers & Promotions** below for everything a voucher can do.

## Opening a POS Register

A cashier opens a **POS Register Session** with a starting cash amount before
taking any sales. All sales, cash movements (cash in/out during the shift), and
payments are tracked against that session. Closing the session reconciles expected
vs. counted cash. This is required before the POS screen can be used
(`pos.access` permission).

## Taking a Sale

The **POS Screen** is the main selling interface: search or scan products, add a
customer (or sell as a walk-in), apply discounts/vouchers, choose a payment method,
and complete the sale. An order can also be:
- **Held** and **resumed** later (e.g. a customer steps away).
- **Reopened** after completion (to add items or fix a mistake), subject to
  permission.
- **Cancelled** or **voided**, each a distinct, individually-permissioned action so
  you can allow "cancel before payment" without allowing "void after payment" for
  the same staff member.

Completing a sale **immediately deducts stock** for every physical item sold, and
records the movement in the stock ledger so you can always trace exactly which sale
consumed which stock. Orders can also be placed on credit — an unpaid or partially
paid order is tracked against the customer, and later settled via
**Customer Payments**.

## Vouchers & Promotions

A voucher can be as simple as "10% off with code SAVE10", or as targeted as a
scheduled, item-specific promotion. Every rule below is optional and can be
combined:

- **Discount type** — a percentage off, a fixed amount off, or a promotional
  offer (Buy One Get One free, or "Buy X Get Y" where the free/discounted item
  is different from what was bought).
- **Scheduling** — a start/end date, specific days of the week (e.g.
  weekends only), and/or a specific time window each day (e.g. happy hour).
- **Minimum order amount** and a **maximum discount cap** (so a percentage-off
  voucher can't discount more than a set amount, however large the cart).
- **Usage limits** — a total number of redemptions, and/or a limit per
  customer.
- **Targeting** — restrict a voucher to specific products, categories,
  brands, or exact product variations; specific customers; specific
  branches/locations; specific sale types, order types, order sources, or
  payment methods.
- **Exclusive vouchers** — a voucher can be marked exclusive, meaning it
  cannot be combined with a manually-applied Discount on the same sale.

**How it applies in POS:** the cashier types a code or clicks the list icon
next to the voucher field to browse vouchers already eligible for the current
cart/customer/branch. Applying shows the discount amount and a plain-English
description of the rule immediately (before completing the sale), or the
exact reason it can't be applied (expired, minimum not met, wrong day/time,
doesn't apply to anything in the cart, etc.). The same checks run again on
the server when the sale is completed, so a voucher can never be applied by
mistake or bypassed from the browser.

**On the order:** the Order Detail page shows a full breakdown — item-level
discounts, the manually-applied Discount (if any), and the Voucher separately
with its code, rule, and amount, plus which line items it actually discounted
or gave free. A partial return correctly gives back only the portion of the
voucher's discount that applied to the returned items.

## Order Returns

A completed sale can be returned in full or in part via **Order Returns**, which
restores the returned stock and reverses the sale's financial impact.

## Customers

Customer records (CRM) are separate from Admin Users — a customer doesn't need a
login to be sold to, but a customer *can* be given a login (used for the customer
mobile/website ordering API). Customer payments and any store credit balance are
tracked per customer, feeding the Customer Ledger and Aging reports.

## Multiple Registers & Branches

Each branch can run its own POS Register(s). Reporting can be broken down by branch,
register, or cashier for accountability.

See also: [Purchasing & Suppliers](04-purchasing-suppliers.md),
[Inventory & Warehouses](05-inventory.md), [Reports](09-reports.md).
