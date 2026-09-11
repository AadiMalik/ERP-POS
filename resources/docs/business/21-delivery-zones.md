# Delivery Zones

Delivery Zones let you charge a delivery fee based on how far a customer's
delivery address is from the branch fulfilling the order - closer customers
pay less, farther customers pay more, and addresses too far away are turned
away automatically with a clear message instead of being accepted and
delivered at a loss.

## How It Works

Each branch (Owner, Business Admin, and Branch Admin can all manage this for
their own branch) defines one or more **distance bands**, for example:

| Distance from branch | Delivery Fee |
|---|---|
| 0 - 5 km | Rs. 200 |
| 5.1 - 8 km | Rs. 350 |

When a customer places a Website or Mobile App order with a delivery
address, the system measures the straight-line distance from the branch to
that address and matches it against these bands:

- **Inside a band** - the matching fee is added to the order total
  automatically, and shown to the customer as "Delivery Fee" before they
  place the order.
- **Outside every band** - the customer sees a clear "out of delivery area"
  message and cannot place the order for that address. They can pick a
  different address or contact the business directly.

The same distance bands also apply on the **POS** when the cashier selects
the **Delivery** order type and picks a location on the map. The suggested
fee is filled in automatically; unlike the website, the cashier can still
change the charge (or enter one by hand on desktop POS, where the map is
not available).

Distance bands must not overlap on the same branch (the system rejects a
zone that overlaps an existing one when you save it), so there is never any
ambiguity about which fee applies.

## Free Delivery

Each branch can also set a **Free Delivery Above Amount** on the Branch
screen (next to the branch's map location). Any order whose total is equal
to or above that amount gets free delivery at that branch, regardless of
distance - the delivery fee shows as **Free** to the customer instead of a
charge. Leave this blank (or 0) to turn the feature off for a branch. On the
POS screen the same rule locks the Delivery Charge input to **0** and shows a
red **Free** label next to it once the cart reaches the amount; the cashier
cannot override the fee until the cart drops below the threshold again.

## Accounting

Delivery fees collected from customers are included in the order total and
posted to the ledger when the order is completed, using the **Delivery
Charge Account** configured under Settings → Accounting (see
[Settings](10-settings.md)). A starter account is already set up for every
business, so no action is needed unless you want delivery income tracked in
a different account.

Delivery fees are never refunded when an order is returned, whether the
return is partial or the whole order - the delivery itself already happened
regardless of what items come back.

## Setting Up

1. Go to **Business Manage → Delivery Zones**.
2. Click **Add New**, pick the branch, and enter the minimum/maximum
   distance (in KM) and the fee for that band.
3. Repeat for as many distance bands as the branch needs.
4. Optionally set **Free Delivery Above Amount** on the branch itself
   (Business Manage → Branch → edit the branch).

A branch needs its map location set (see the branch's **Branch Location**
map) before its Delivery Zones can be matched against a customer's address -
without it, every address for that branch is treated as out of delivery
area.
