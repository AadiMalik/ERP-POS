# Sales & Point of Sale (POS)

## Setup

Before taking sales, configure the building blocks under **Sales Setup**:
- **Order Types** — e.g. dine-in, takeaway, delivery.
- **Payment Methods** — cash, card, bank transfer, store credit, etc.
- **Order Sources** — POS, website, mobile app.
- **Sale Types** — pricing tiers (e.g. retail vs. wholesale).
- **Discounts** and **Vouchers** — percentage/fixed discounts and redeemable
  vouchers, each scoped to specific products, categories, customers, order types,
  or branches if needed.

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
