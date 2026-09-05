# Advanced Analytics & Business Intelligence

## Purpose
One package-gated Analytics dashboard that **composes** existing Dashboard and Report services so numbers never diverge from Home / Reports. Only two metrics are new (product margin, customer segments); slow-moving is a thin filter over `StockAgingReportService` velocity mode.

## Gating
- **Package:** `SubscriptionModuleRegistry` key `analytics` → route group `middleware: module:analytics` in `routes/web.php`.
- **RBAC:** `analytics.view` / `analytics.export` in `PermissionRegistry`. Defaults: Business Admin (via `businessNames()`), Reporting Analyst, and operational roles (`operationalModuleKeys()` includes `analytics`).
- **Sidebar:** `businessModuleEnabled('analytics')` + `@canAccess('analytics.view')`.

## Stack
| Piece | Path |
|---|---|
| Controller | `app/Http/Controllers/Admin/AnalyticsController.php` |
| Orchestrator | `app/Services/Concrete/Admin/Analytics/AnalyticsService.php` |
| Access / filters | `AnalyticsAccessService`, `AnalyticsFilterOptionsService` |
| Period compare | `Analytics/Support/PeriodComparisonService.php` |
| New metrics | `ProductMarginReportService`, `CustomerSegmentService` |
| Reuse wrapper | `SlowMovingProductService` → `StockAgingReportService` |
| Export | `app/Exports/Analytics/AnalyticsWidgetExport.php` |
| Views / JS | `resources/views/admin/analytics/*`, `public/assets/js/admin/analytics.js` |
| Lang | `lang/*/analytics.php`, `lang/*/sidebar.php` key `analytics` |

## Routes
Prefix `admin/analytics` (inside `module:analytics`):
- `GET /` → `index`
- `GET data/{widget}` → JSON KPI/chart payload (widget whitelist in `AnalyticsService::WIDGETS`)
- `POST table/{widget}` → tabular JSON
- `GET export/{widget}` → Excel (`analytics.export`)

`{widget}` is never used to call an arbitrary method from user input — only camelCase of a whitelist entry.

## Caching convention (new)
`AnalyticsService::widgetData()` is the **first** `Cache::remember()` usage in the admin Dashboard/Reports layer. TTL 300s. Key:

`analytics:{business_id}:{branch|all}:{widget}:{md5(filter fingerprint)}`

Caching lives only in this orchestrator — reused report services stay uncached.

## Composition map
| Widget | Source |
|---|---|
| sales-overview | `OrderService` + returns sum + optional `PeriodComparisonService` |
| purchases-overview | `PurchaseService` + optional period compare |
| inventory-summary | `DashboardInventoryService` |
| finance-summary | `DashboardFinanceService` (only when `scope['is_finance']`) |
| top-products / customers / branch / source / payment / loyalty | existing `Reports\Orders\*` services |
| product-margin | **new** — `order_details.cost_price` snapshot, nets approved returns |
| customer-segments | **new** — first-ever order date vs range → new / returning / walkin |
| slow-moving | `StockAgingReportService` `report_mode=velocity`, filter slow+non_moving |

## Formulas (new metrics)
**Estimated margin (per product):**
`net_revenue = SUM(order_details.total) − SUM(approved return totals)`
`estimated_cogs = SUM(cost_price × qty) − SUM(return cost_price × return_qty)`
`estimated_margin = net_revenue − estimated_cogs`

**Customer segment:** first posted order date (all-time, business-scoped). In-range order with `user_id` null → walkin; first order date inside range → new; else returning.

## Invariants
1. Reused widgets must match Home Dashboard / matching Report for the same scope.
2. Sum of product-margin `net_revenue` ≈ Net Sales for the period (same posted sales − returns grain).
3. Slow-moving row set ⊆ Stock Aging velocity output.
4. `new.order_count + returning.order_count + walkin.order_count` = period `total_orders`.

## Related
- [Reports Infrastructure](06-reports-infrastructure.md)
- [Subscription & Module Gating](08-subscription-module-gating.md)
- Business doc: [Advanced Analytics & BI](../business/20-analytics-bi.md)
