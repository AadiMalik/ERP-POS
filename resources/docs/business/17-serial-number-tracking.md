# Serial Number Tracking

For products where every individual unit matters — electronics, appliances,
anything with a manufacturer serial number or IMEI, or anything you offer a
warranty on — Serial Number Tracking lets you track each physical unit
separately, from the moment it arrives to the moment it's sold, and beyond.

It's entirely optional and works alongside your normal stock. Nothing about
how a regular (non-serialized) product works changes — you only see serial
number screens and prompts for the specific product variations you turn this
on for.

## Turning it on for a product

On a Product's variation form, next to the existing "Track Batch" and "Track
Expiry" checkboxes, there's a new **Track Serial Number** checkbox. Turn it
on for any variation where you want every unit individually tracked — a
specific phone model, a specific laptop configuration, a specific brand of
appliance. Leave it off for everything else (groceries, clothing, anything
sold by simple quantity).

## Receiving serial-tracked stock

When you receive a serial-tracked product — through a Purchase, a Goods
Receipt Note, or Opening Stock — the line for that product shows a "Serial #"
button instead of just a quantity. Click it and either type in each unit's
serial number or scan it with your camera (the same scan button used
elsewhere in the system), one per physical unit received. The number of
serials you enter has to match the quantity on that line exactly — the
system won't let you save a mismatch.

## Selling a serial-tracked product

At the POS, adding a serial-tracked product to the cart opens a picker
showing which units are actually in stock at your selling location. Select
(or scan) the specific unit(s) being sold — the cart quantity always matches
however many you've picked, so there's no separate quantity field to keep in
sync. If you need to sell more than one, the picker asks for that many.
Already added a serial-tracked item to the cart? A "Manage Serials" button on
that cart line lets you add or remove units before checkout.

## Warehouse transfers

Sending a serial-tracked product to another warehouse asks you to pick which
specific units are being sent (from what's actually on hand at the source).
When the receiving warehouse confirms the transfer, they pick from the units
that are in transit to them. If a transfer note is sent but never received,
cancelling it puts those units straight back into available stock at the
source.

## Returns

**Customer returns** show you exactly which serial numbers were sold under
that order, so you pick which physical unit(s) are actually coming back —
they go straight back into available stock at the branch that sold them.

**Supplier returns** work the same way against a purchase or GRN line — pick
which of the received units you're sending back, and they're removed from
your sellable stock.

## Damaged, lost, or expired units

If a specific serialized unit is damaged, lost, or otherwise needs writing
off, record it through the existing Waste / Damage / Expiry screen the same
way you would for any other product — for a serial-tracked line, you pick
the specific unit(s) affected instead of just a quantity.

## Warranty and repairs

If a product has a warranty, the serial number's warranty end date is set
automatically at the time of sale. The Serial Number Details page for any
unit shows a **Send for Repair** and **Return from Repair** action to track a
unit going out for servicing and coming back, and a **Replace This Unit**
action for a straight swap — the old unit is retired and, if it had been
sold, the replacement is automatically handed to the same customer's order.

## Finding a serial number

The **Serial Numbers** screen (under Inventory) lets you search by serial
number, product, warehouse, or status. Click any result to open its full
history: where it came from (which purchase or delivery), where it is now,
who bought it and when (if sold), its warranty status, and a complete
timeline of everything that's happened to it — received, transferred, sold,
returned, repaired, whatever the case may be.

If you physically find a unit that was never entered into the system (for
example while doing a stock count), use **Add Found Unit** on the Serial
Numbers screen to record it — this also adds it to your stock count for that
warehouse, the same as if you'd just received it.

## Stock counts (Stock Taking)

For serial-tracked products, a Stock Taking session shows the system's count
as read-only — you can't type in a different number, because the count is
generated from the actual serial numbers on hand, not typed in by hand. If a
count doesn't match what's physically there, find the specific missing or
extra unit using the Serial Numbers screen above (mark it lost/damaged, or
add it as a found unit) rather than adjusting a blind quantity.

## Reports

Under Inventory Reports → **Serial Number Reports**:

- **Serial Number Register** — every serial number your business has ever
  recorded, with its current status.
- **Available Serial Numbers** — what's currently in stock and sellable, by
  warehouse.
- **Sold Serial Numbers** — every unit currently with a customer.
- **Serial Number Movement History** — the complete audit trail of every
  unit's journey, across your whole business.
- **Customer-wise Serial Numbers** — every unit a specific customer has
  bought, with warranty status — handy for support/warranty lookups.

Each report supports Print, PDF, and Excel/CSV export, same as every other
report in the system.
