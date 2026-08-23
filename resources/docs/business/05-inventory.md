# Inventory & Warehouses

## Catalog Structure

Products are organized as: **Category** → **Sub-Category** → **Brand**, with each
**Product** measured in a base **Unit** (with optional purchase/sale unit
conversions, e.g. buying by the case but selling by the piece). A product can have
multiple **Variations** (e.g. size/color) — the variation is the actual thing that's
bought, sold, and stocked, each with its own SKU, barcode/QR code, and pricing.

## Warehouses & Stock

Stock is tracked **per warehouse, per product variation**. Every stock-affecting
transaction — a sale, a purchase receipt (GRN), a return, a transfer, a manual
adjustment — writes an entry to the **stock ledger**, so you can always trace
exactly why your stock level is what it is and who/what changed it.

- **Opening Stock** sets the starting balance when a warehouse or product is first
  set up.
- **Stock Taking** lets you record a physical count and reconcile it against what
  the system expects, generating adjustment entries for any difference.
- **Transfer Notes** move stock between warehouses or branches.
- **Batches** track expiry/lot numbers where relevant.

## Barcodes & Labels

Each product variation can have a barcode/QR code, printable as labels for shelf or
packaging use. A backfill tool exists to generate barcodes for older products that
don't have one yet.

## Pricing

Product variations carry purchase price and one or more sale prices; a price
history is kept automatically whenever pricing changes, and sale-type-specific
pricing (e.g. a wholesale price) can be configured per variation.

See also: [Purchasing & Suppliers](04-purchasing-suppliers.md) (how stock arrives),
[Sales & Point of Sale](03-sales-pos.md) (how stock leaves).
