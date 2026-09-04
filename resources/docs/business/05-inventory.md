# Inventory & Warehouses

## Catalog Structure

Products are organized as: **Category** → **Sub-Category** → **Brand**, with each
**Product** measured in a base **Unit** (with optional purchase/sale unit
conversions, e.g. buying by the case but selling by the piece). A product can have
multiple **Variations** (e.g. size/color) — the variation is the actual thing that's
bought, sold, and stocked, each with its own SKU, barcode/QR code, and pricing.

On your website homepage, products can appear in Featured, Trending, New Arrivals,
Best Sellers, and Discounted sections. Mark products as Featured, Trending, or
Best Seller on the product form so they are preferred for those rails. If you have
fewer flagged products than the section shows, the site fills the rest with other
visible products so the section is not left empty. The Discounted section only
lists products that currently have a real discount — if none do, that section is
hidden (no empty heading or “products not found” message).

## Warehouses & Stock

Stock is tracked **per warehouse, per product variation**. Every stock-affecting
transaction — a sale, a purchase receipt (GRN), a return, a transfer, a manual
adjustment — writes an entry to the **stock ledger**, so you can always trace
exactly why your stock level is what it is and who/what changed it.

- **Opening Stock** sets the starting balance when a warehouse or product is first
  set up.
- **Stock Taking** lets you record a physical count and reconcile it against what
  the system expects, generating adjustment entries for any difference. It
  reconciles the overall per-warehouse total only; it does not currently
  break the count down by batch.
- **Transfer Notes** move stock between warehouses or branches, through a controlled
  workflow so stock is never lost or double-counted in transit:
  - **Draft** — build the transfer (source/destination warehouse, products, quantities).
    Nothing happens to stock yet, and it can still be freely edited or deleted.
  - **Send** — the source branch dispatches it. The quantity is deducted from the
    source warehouse right away and held as **In Transit** — it's no longer available
    to sell or transfer again at the source, but it hasn't landed at the destination
    yet either.
  - **Receive** — the destination branch confirms what actually arrived. Only at this
    point does the stock become available at the destination. If a shipment arrives
    short (breakage, a partial delivery), the destination can receive less than what
    was sent — the transfer stays "In Transit" showing the remaining quantity until
    the rest is received (or never, if it's not coming). The system won't let a
    destination receive more than was sent, and won't let the same quantity be
    received twice.
  - **Cancel** — allowed while still Draft or In Transit, but only before the
    destination has received anything; once any quantity has been received the
    transfer can no longer be cancelled.

  Only the source branch can Send or Cancel a transfer, and only the destination
  branch can Receive it (Business Admins and Inventory Managers can act on either
  side). The Transfer Note print-out shows who sent it and when, and who received it
  and when, in addition to the products and quantities.

  Batch/lot quantities are not currently moved by a Transfer Note — a
  batch-tracked product can still be transferred, but its batch record stays
  parked at the source warehouse. This is a known gap, planned as a follow-up.

## Batches & Expiry Tracking

Batch/lot and expiry tracking is **optional per product variation** — turn on
**Track Batch** and/or **Track Expiry** on a variation's form only for
products that actually need it (e.g. food, pharmaceuticals, cosmetics).
Products without either flag continue to work exactly as before: one stock
quantity per warehouse, no batch number or expiry date anywhere.

For a batch/expiry-enabled variation:

- **Receiving** (a direct Purchase, or a GRN against a Purchase Request) asks
  for a **Batch No.**, and an **Expiry Date** if the variation tracks expiry,
  alongside the quantity received. Receiving the same batch number again for
  the same product/warehouse adds to that batch rather than creating a
  duplicate.
- **Stock** for that variation is tracked both as one overall total per
  warehouse (as before) and broken down per batch, so you always know not
  just *how much* you have but *which batch* it's in and *when it expires*.
- **Selling** automatically draws from the right batch (see
  [Sales & Point of Sale](03-sales-pos.md) for
  the FEFO/FIFO and expired-stock rules) — there's nothing extra for the
  cashier to do.
- **Purchase Returns** and **Order (Sale) Returns** adjust the specific
  batch the stock came from, not just the warehouse total.

**Batch Stock report:** the **Batches** screen under Inventory lists every
batch with its product, warehouse, quantity, cost, and expiry date, and can
be filtered by an **Expiry Status** of Active, **Near Expiry**, or
**Expired** — the Near Expiry window (default 30 days) is configurable under
**Settings → Inventory → Near Expiry Threshold**.

## Barcodes & Labels

Each product variation can have a barcode/QR code, printable as labels for shelf or
packaging use. A backfill tool exists to generate barcodes for older products that
don't have one yet.

## Pricing

Product variations carry purchase price and one or more sale prices; a price
history is kept automatically whenever pricing changes, and sale-type-specific
pricing (e.g. a wholesale price) can be configured per variation.

See also: [Purchasing & Suppliers](04-purchasing-suppliers.md) (how stock arrives),
[Sales & Point of Sale](03-sales-pos.md) (how stock leaves),
[Manufacturing & Production](16-manufacturing.md) (how production consumes and creates stock).

## Inventory Reporting System

Under **Inventory → Reports**, stock, consumption, manufacturing, and recipe/BOM
reports are grouped together so production is treated as part of inventory flow
(raw materials → consumption → finished goods → ledger), not a separate reporting
silo.

### Stock Reports
- **Stock Summary / Availability / Low Stock** — on-hand, reserved, available,
  value, minimum stock, and reorder quantity. Switch the Report View filter for
  availability-only or low-stock/reorder focus. Click a product to open its
  Stock Ledger.
- **Stock Ledger / Product Ledger** — every posted stock movement with opening/
  closing balance when filtered to one item. Also serves Product Ledger when you
  narrow to a product/variation/warehouse.
- **Stock Valuation** — quantity × moving-average cost (`avg_price`).
- **Stock Aging / Slow-Fast-Non-Moving** — days since last movement, age buckets,
  and velocity class.
- **Stock Transfer** — transfer notes between warehouses/branches.
- **Reconciliation & Adjustment** — stock-taking differences, or posted adjustment
  / stock-take movements.
- **Loss / Wastage / Damage** — ledger rows typed as damage, wastage, or expired.
  (Dedicated damage/wastage note documents are not a separate module yet.)
- **Batch/Lot & Expiry** — batch quantities and near-expiry / expired filters.

### Consumption, Manufacturing & Recipe Reports
When the Manufacturing package module is enabled, these also appear under
Inventory → Reports:
- **Material Consumption Analysis** — detail plus material/product/category/
  warehouse/production/recipe/plan grouping, and Expected vs Actual variance
  against manufacturing plan materials.
- **Manufacturing Plan** and **Production** (summary, yield, costing, variance,
  wastage proxy, traceability via Report View).
- **Recipe/BOM** — components, cost analysis, material requirement for a produce
  quantity, and recipe coverage (manufactured variations with/without a recipe).

All of these support Print / PDF / Excel / CSV where your role allows, and respect
branch/warehouse access rules.
