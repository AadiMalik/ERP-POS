# Purchasing & Suppliers

This module covers the full procure-to-pay flow for physical goods, from
requesting a quote to paying the supplier.

## The Purchasing Flow

1. **Purchase Request** — an internal request to buy something (e.g. raised by a
   branch when stock runs low), which can go through an approval step.
2. **Purchase Request Quotation (RFQ)** — request quotes from one or more suppliers
   for that request; quotations can be generated as a PDF and sent to suppliers
   automatically.
3. **Purchase** — a confirmed purchase order raised against a supplier, either
   directly or from an approved request/quotation, listing what was ordered and at
   what price.
4. **Good Receipt Note (GRN)** — records what's actually received against a
   Purchase (goods often arrive in partial batches). Every GRN **increases stock**
   for the received quantities and automatically posts the corresponding accounting
   entry, so your inventory value and books stay in sync as goods physically arrive
   — not just when the purchase order is raised.
5. **Purchase Return** — send goods back to a supplier, either against a specific
   GRN or a direct purchase; this reverses the stock increase and its accounting
   entry.
6. **Supplier Payment** — pay the supplier for what's been received, tracked
   against the supplier's running ledger (Accounts Payable).

## Suppliers

The **Suppliers** master list holds contact and payment details for each vendor,
linked to their own ledger account so every purchase, GRN, and payment they're
involved in rolls up into a single running balance (see the Supplier Ledger and
Supplier Aging reports under [Reports](09-reports.md)).

## Other Stock Movements

Outside of purchasing, stock can also change through:
- **Opening Stock** — the starting balance when you first set up a warehouse/product.
- **Stock Taking** — a physical count reconciled against the system's recorded
  quantity.
- **Transfer Notes** — moving stock between warehouses or branches.

See [Inventory & Warehouses](05-inventory.md) for how stock is organized, and
[Accounting & Bookkeeping](07-accounting.md) for how these transactions post to
your books.
