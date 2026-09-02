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
(`pos.access` permission). The session is always opened for the signed-in
cashier's own business (and branch, when their account is branch-scoped) —
staff cannot open a register under another business.

## Taking a Sale

The **POS Screen** is the main selling interface: search or scan products, add a
customer (or sell as a walk-in), apply discounts/vouchers, choose a payment method,
and complete the sale.

**Cart header:** the customer dropdown (with a quick **+** button to add a new
customer) sits on the same line as the cart title, styled like the Sale Type
dropdown. Customers appear as **Code - Name** and the list is searchable
(by code, name, phone, or email). **Payment & Options** (payment method, order
discount, voucher/coupon) opens from a small bookmark-style clip on the side
of the product area — collapsed by default so more room is left for browsing
products. The clip label shows the current payment method (Cash is selected
automatically on a fresh sale). For **Delivery** order types, opening that
panel shows the delivery address and payment method on the same row.

An order can also be:
- **Held** and **resumed** later (e.g. a customer steps away).
- **Reopened** after completion (to add items or fix a mistake), subject to
  permission.
- **Cancelled** or **voided**, each a distinct, individually-permissioned action so
  you can allow "cancel before payment" without allowing "void after payment" for
  the same staff member.

**Updating order status:** open **Orders → View** on any order. The action
buttons at the top depend on the current status and your permissions:
- **Complete Order** — for Draft/Hold orders; posts the sale (deducts stock and
  books accounting). Website delivery orders can be completed here without going
  through POS.
- **Cancel Order** — for Draft/Hold only; requires a reason.
- **Void Order** — for Posted orders only; reverses stock/accounting; requires a
  reason.
- **Mark Shipped / Out for Delivery / Mark Delivered** — for **Delivery** order
  type only, after the order is posted. These are fulfilment tracking steps and
  do not change stock or accounting again.

Every change is recorded in the order's **Status History** section on the same
page. The Orders list also offers **Cancel** on Draft/Hold rows.

Completing a sale **immediately deducts stock** for every physical item sold, and
records the movement in the stock ledger so you can always trace exactly which sale
consumed which stock. Orders can also be placed on credit — an unpaid or partially
paid order is tracked against the customer, and later settled via
**Customer Payments**.

## Stock Availability in POS

For every stock-tracked product, the POS screen shows how much is currently
available at the register's own warehouse — right on the product card for a
single-variation product, and per variation in the picker when a product has
more than one. A product that isn't stock-tracked shows no stock figure at
all (it's treated as unlimited). This figure is always the live warehouse
quantity, not a cached snapshot — it's refreshed every time products are
searched/browsed, whenever the Sale Type changes (which re-prices the cart),
and whenever a held order is resumed.

By default, a cashier cannot add more of a product than is currently
available, and a product already at zero (or negative) stock can't be added
at all — this applies whether adding a new item, using the quantity +/-
steppers, or typing a quantity directly. Business owners who want to allow
selling past available stock (e.g. to take an order that will be fulfilled
by an incoming purchase) can turn this restriction off via **Settings →
Inventory → Negative Stock** — with it enabled, POS behaves as before and
never blocks on quantity.

Because a held order can sit for a while before it's resumed, its stock is
never assumed to still be valid: resuming a held order re-checks every
line's stock against current inventory. An item that's gone out of stock in
the meantime is automatically removed from the cart, and a line whose held
quantity now exceeds what's available is automatically reduced to the
available amount — the cashier is shown exactly what changed. Stock is
checked once more, authoritatively, the instant the sale is completed, so
two cashiers racing for the last unit of the same product can never both
succeed.

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
mobile/website ordering API). Signup, sign-in, and **Forgot Password** codes from
your website are emailed with your business name, logo, and website colors, with
**Powered by Dukanaz** only at the bottom. For password reset the email must
already be registered as a customer of your business; otherwise they see that
the email is not registered. Customer payments and any store credit balance are
tracked per customer, feeding the Customer Ledger and Aging reports.

## Multiple Registers & Branches

Each branch can run its own POS Register(s). Reporting can be broken down by branch,
register, or cashier for accountability.

## Desktop POS (Windows, offline)

In addition to the browser POS, you can install the **ERP Desktop POS** Windows
application (separate **`erp-desktop-pos`** repo at
`C:\xampp\htdocs\erp-desktop-pos`). It looks and works like the
web POS for day-to-day selling, but keeps a local copy of products, prices,
stock, customers, settings, and staff permissions so cashiers can keep working
when the internet drops.

**First install (internet required once):** connect to your ERP URL, log in,
register the PC as a POS device, and download data. After that, the app opens
and sells offline using the last synced data.

**When internet returns:** orders, register sessions, cash movements, and new
customers queue up and sync automatically to the central ERP (you can also tap
**Sync Now**). The status bar shows Online, Offline, Syncing, Pending orders,
or Sync errors without blocking checkout.

The web POS is not replaced — website, mobile, other registers, and back-office
changes still flow through the same central inventory and orders.

See also: [Purchasing & Suppliers](04-purchasing-suppliers.md),
[Inventory & Warehouses](05-inventory.md), [Reports](09-reports.md).
