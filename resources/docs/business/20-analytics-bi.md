# Advanced Analytics & Business Intelligence

## What it is
A single owner-friendly Analytics screen that brings together sales, purchases, inventory, finance, loyalty, and a few deeper insights (product margin, new vs returning customers, slow-moving stock) with optional period-over-period comparison arrows.

## Who can use it
1. Your subscription package must include the **Advanced Analytics & BI** add-on (ask your Super Admin / package settings).
2. Your role needs the **View** permission for Analytics (`analytics.view`). Exporting tables needs **Export** (`analytics.export`).

If either is missing, the sidebar link is hidden and the page is blocked.

## What you will see
- **Filters** — date range, branch (when allowed), order type/source/payment method, plus product, category, brand, and customer. Turn on **Compare to previous period** to show up/down % badges on sales KPIs.
- **KPI cards** — Total Sales, Net Sales (sales minus approved returns), Orders, Average Order Value, Purchases, Stock Value / Low Stock, and (for finance roles) Net/Gross Profit and Expenses. Product Margin totals show an **Estimated** badge.
- **Charts** — Sales trend, top products/customers, branch comparison, order source and payment method breakdowns, and customer segments (new / returning / walk-in).
- **Tables** — Product Margin (Estimated) and Slow / Non-Moving products, with Excel export when you have export permission.

## Estimated vs Authoritative
| Widget / KPI | Tag | Why |
|---|---|---|
| Product Margin (revenue − line cost) | **Estimated** | Uses the cost snapshot stored on each order line, not the accounting ledger. |
| Everything else (sales, purchases, inventory, finance, loyalty, slow-moving) | **Authoritative** | Same numbers as the Home Dashboard and existing Reports for the same filters. |

Never treat Estimated Margin as a replacement for Profit & Loss Net Profit.

## Tips
- Net Sales = Total Sales − approved returns in the same range.
- New customers = their first-ever posted order falls inside the selected dates. Returning = they ordered before the range. Walk-in = orders with no customer attached.
- Slow-moving rows match the Stock Aging report in **velocity** mode (slow + non-moving only).
