# Loyalty Program

The Loyalty Program rewards customers with points for the purchases they make,
which they can later redeem as a discount on a future order. It is entirely
optional and switched off by default.

## Turning It On

Go to **Settings → Customer** and find the **Loyalty Program** section:

- **Enable Loyalty Program** — set to *Yes* to switch it on. While it's off,
  every loyalty-related feature stays hidden across POS, the website, the
  mobile app, the customer profile, and reports.
- **Earn Points Based On** — choose how purchases qualify for points:
  - **Overall Order** — every paid order earns points on its full total.
  - **Individual Product / Variation** — only products/variations your
    business has specifically marked as loyalty-eligible count toward
    earning; everything else in the same order earns nothing.
- **Every Purchase Amount** and **Loyalty Points** — together these set the
  earn rate, e.g. "1 point for every Rs 100 spent." A customer earns whole
  multiples only — Rs 250 spent at a Rs 100 / 1 point rate earns 2 points,
  not 2.5.
- **Minimum Order Amount** — an order below this amount never earns points,
  even if the program is on.
- **Point Redemption Value** — the currency value of a single point when a
  customer spends it as a discount (e.g. Rs 2 per point).

Only users with the **Manage Settings** permission can change these.

## Choosing an Earning Mode

**Overall Order** is the simpler option — every qualifying order earns points
on its whole total, with no extra setup. **Individual Product / Variation**
is useful when you only want to reward purchases of certain items (e.g.
full-price stock, not already-discounted clearance items) — only the portion
of the order made up of loyalty-eligible products/variations counts toward
the earn calculation. When this mode is on, a "Loyalty Enabled" checkbox
appears on the Product create/edit screen — both for the product as a whole
and for each individual variation — so you can mark exactly which
products/variations count toward loyalty earning.

## How Customers Earn Points

Points are only awarded once an order is actually **paid or completed** —
never on a draft, held, or unpaid order. At that moment, the system checks
the order against the Minimum Order Amount and the chosen earning mode, then
credits the points to the customer's balance. The number of points earned is
locked onto that order permanently, so a later change to your Loyalty
Program settings never rewrites the history of an order that already went
through.

## Redeeming Points

A customer's available points can be spent as a discount on an order,
lowering how much they owe. Redemption can never exceed the order's payable
total — a customer can bring an order to Rs 0 with points, but never into
negative territory, and any points that would over-shoot the total simply
aren't used. Point redemption is available through your website checkout,
mobile app checkout, and the in-store POS screen — a cashier ticks "Use
Loyalty Points" in the cart's checkout panel (next to Discount/Voucher),
where the customer's available points are shown so they can decide before
completing the sale.

While points are attached to an order that hasn't been paid yet (e.g. it's
sitting as a website order awaiting payment), they're **reserved** — taken
out of the customer's spendable balance so they can't be redeemed twice on
two different orders at once — but not yet permanently spent. They're only
permanently deducted once that order is actually paid or completed.

## Cancellations, Voids & Returns

- **Cancelling** a draft or unpaid order that had points reserved against it
  releases those points straight back to the customer's available balance —
  nothing is lost, since the order was never paid.
- **Voiding** a completed order reverses everything the sale did to the
  customer's loyalty balance: any points the customer redeemed on it are
  given back, and any points the order had earned are taken back.
- **Returning** an order (in full or in part) takes back a share of the
  points that order earned, proportional to how much of the order was
  returned — return half the order's value, and half the earned points are
  taken back. If that return is later reversed/cancelled, the points are
  restored.

## Viewing a Customer's Points Balance

A customer can see their own available and reserved points through their
website or mobile app account — their profile page shows an Available/Reserved
summary, and a dedicated Loyalty Points history view lists every
point-earning, redemption, and adjustment event for their account, newest
first. Products and variations that count toward earning are marked with a
small gold coin badge wherever they're shown for sale on the website and
mobile app, so a shopper can tell at a glance which items will earn them
points before they buy. On the back office, the Customer profile page shows
the same Available and Reserved balances alongside Store Credit, plus a
Loyalty History tab listing every point-earning, redemption, and adjustment
event for that customer. An order's own receipt/details page also shows how
many points were redeemed (and their discount value) and how many points it
earned — both on the Order Details screen and on the printed POS receipt.

## Accounting

When a customer redeems points on a sale, the discount is recorded against a
dedicated **Loyalty Points Discount** account (kept separate from your
regular Discount account) so your accountant can see exactly how much
revenue was given up to loyalty redemptions. This account is set up
automatically for every business and can be changed under **Settings →
Accounting** if you'd rather point it somewhere else.

See also: [Settings](10-settings.md), [Sales & Point of Sale (POS)](03-sales-pos.md),
[Accounting & Bookkeeping](07-accounting.md).
