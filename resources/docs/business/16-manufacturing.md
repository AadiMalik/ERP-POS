# Manufacturing & Production

Manufacturing is an optional module, enabled per-package by your platform provider.
It lets a business that makes things — a bakery, a workshop, a food producer, or any
business that converts raw materials into finished goods — plan and record production
runs without leaving the same Inventory, Warehouse, Purchase, Sales, and Accounting
system everything else in the ERP already uses.

It is deliberately generic: nothing here is bakery-specific. A "recipe" is just a
Bill of Materials — a list of raw materials and quantities needed to make one unit
of a finished product — and it works the same way whether you're making cakes,
furniture, gas refills, or assembled electronics.

## Raw Materials and Finished Goods Are Just Products

A raw material and a finished (manufactured) good are both ordinary Products/Product
Variations in the system — there's no separate "ingredient" catalog and no switch to
flip. Any product/variation in the business can be used as a raw material in a
recipe, and any product/variation can be the finished output of one — the only rule
is that a recipe can't consume the very product/variation it manufactures.

## Recipes / Bill of Materials (BOM)

A Recipe attaches to one finished-good product variation, and there is only ever
**one recipe per variation** — there's no recipe list to browse and no version
history. Instead there's a single Recipe page: pick the finished product and
variation, and if a recipe already exists for that variation it loads right there,
ready to edit in place. Add or remove a raw material line and it's saved instantly —
the same recipe row is updated, nothing is versioned or archived, and there's no
separate "Save Recipe" step.

Each raw material line is added through a small popup, saved immediately:

- **Product and variation** — every product except the one you're manufacturing
  (a recipe can't consume itself).
- **Quantity**, always understood in that raw material's own base unit (shown for
  you, no conversion to think about).
- **Consume From (Warehouse)** — which warehouse this specific raw material is
  drawn from. A Business Admin can pick any warehouse in the business; a
  branch-level user only sees their own branch's warehouses. Different lines in
  the same recipe can draw from different warehouses (e.g. flour from the main
  store, packaging from a branch store).

There's no output-quantity/yield setting, no wastage percentage, no by-products, and
no production-step template on the recipe — one line, one raw material, one
warehouse, one quantity per finished unit. Keeping it simple here keeps the Plan and
Production math simple too: everything is a straight multiply by planned/produced
quantity.

## Manufacturing Plans: Planning Without Touching Stock

A Manufacturing Plan is *intent*, not production. Creating a plan for "100 units of
Product X" does **not** add 100 units to your stock — it only becomes real once you
**Approve** the plan.

Creating a plan is simple: pick the product and variation you want to manufacture -
its recipe loads automatically underneath (no separate recipe picker), showing each
raw material line with its quantity per unit and its warehouse. Type the planned
quantity and the required quantity for every raw material recalculates live as you
type, so you can see exactly what this plan will need before saving it.

Approving a plan:

1. Explodes the recipe × planned quantity into the exact raw material quantities
   needed.
2. **Reserves** that quantity from each raw material's own warehouse (as set on the
   recipe line - different raw materials can reserve from different warehouses).
   The materials aren't consumed yet — reserving just holds them so they can't be
   accidentally sold or transferred out from under the plan by another order at
   the same time.
3. Moves the plan to **Not Complete** status, after which you can start creating
   Productions against it.

If there isn't enough *available* stock (on-hand minus anything already reserved
by other plans) to cover the plan, approving is blocked with a clear message
telling you what's short.

Cancelling a plan releases whatever reservation is still outstanding back to free
stock.

## Productions: Where Stock Actually Moves

A single plan can have many independent Productions — this is the key idea for a
multi-branch or multi-batch business. Plan 100 units, then:

- Production #1: 20 units → Branch A warehouse, made Sept 3, expires Sept 6.
- Production #2: 80 units → Branch B warehouse, made Sept 4, expires Sept 7.
- ...and so on, until the plan's 100 units are fully produced (the plan then moves
  to **Completed**), or you stop early, leaving the plan **Not Complete** so more
  Productions can still be added against it later. A production's quantity can
  never exceed what's still remaining on its plan — pick the plan first and the
  screen shows you the remaining quantity before you type anything.

Each Production has its own quantity, warehouse, manufacturing date, and expiry
date — never one shared batch/expiry across the whole plan. The batch/lot number
is generated for you automatically (it's the same as the production's own
reference number) - there's nothing to type. If finished goods need to move to a
different branch after production, use the normal **Stock Transfer** feature —
Manufacturing doesn't have its own separate transfer mechanism.

A Production only affects stock when you **Approve / Complete** it:

- The raw materials it needs (scaled to *this production's* quantity) are consumed
  from each raw material's own warehouse (as set on the recipe) — drawing from the
  correct batches automatically if those raw materials are batch/expiry-tracked
  (First-Expiry-First-Out by default, same rule as a normal sale).
- The finished quantity is added to *this production's* chosen warehouse, under its
  own batch/expiry.
- Costing is calculated automatically: raw material cost (at current average
  cost) plus whatever Labor/Overhead/Other cost you entered for this run, divided
  across the quantity produced to get a per-unit cost — the same accounting used
  everywhere else in the system picks this up automatically.

If a completed Production turns out to be wrong, it can be **Voided / Reverted** —
this reverses the stock movement exactly (raw materials go back, finished goods come
back out), re-reserves the materials against the plan, and drops the plan back to
**Not Complete** if it had already been marked Completed, so you can redo it.

## Traceability

Every Production keeps a full record of exactly which raw material batches were
consumed and how much of each. This gives you both directions of traceability:

- **Forward**: open a finished-goods batch → see its Production → its parent
  Plan → the exact raw material batches that went into it.
- **Backward**: open a raw material (or one of its batches) → see every Production
  that ever consumed it, and which finished-goods batches came out of those runs.

## Where to Find It in Reports

Manufacturing reports are listed under **Inventory → Reports** (Consumption,
Manufacturing, and Recipe/BOM sub-sections) so production stays tied to stock
flow. The Manufacturing menu still holds Recipes, Plans, and Productions.

- **Manufacturing Plan Report** — every plan, its plan date, planned/produced/
  remaining quantity, and progress.
- **Production Report** — summary/detail with Report View modes for performance/
  yield, costing, variance, wastage proxy (expected vs actual material), and
  traceability links into consumption and the stock ledger.
- **Material Consumption Report** — detail plus group-by (material, finished
  product, category, warehouse, production, recipe, plan/order) and Expected vs
  Actual / variance against plan materials.
- **Recipe/BOM Report** — recipe lines, cost analysis, material requirement for
  a produce quantity, and coverage (manufactured variations with/without a
  recipe). Recipes are not versioned — one recipe per finished variation.
- The existing **Stock Ledger** and other Stock Reports under Inventory also
  show manufacturing movements (Production In/Out, Consumption) alongside
  Purchases, Sales, and Transfers.
