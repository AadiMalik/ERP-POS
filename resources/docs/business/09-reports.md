# Reports

Reports pull live data straight from your day-to-day transactions — nothing is
pre-computed or stored separately, so a report always reflects the current state
of your books and stock. Every report screen offers the same set of actions
(subject to your permissions): filter, **Print**, **PDF**, **Excel**, and **CSV**
export.

## Financial Reports (Accounting)
General Ledger, Trial Balance, Journal Register, Account Ledger, Account Balance,
Day Book, Cash & Bank Ledger, Income Report, Expense Report, Expense Detail
Report, Tax Report, Equity Report, **Profit & Loss**, **Balance Sheet**, **Cash
Flow Statement**, Budget vs. Actual, plus Fixed Asset reports: **Fixed Asset
Register**, **Depreciation Report**, **Asset Valuation / Book Value**, and
**Asset Disposal / Sale**.

The **Cash Flow Statement** shows cash inflows and outflows for a date range,
classified into Operating, Investing, and Financing activities from your posted
journal entries. It includes opening and closing cash/bank balances and a
reconciliation that opening plus net cash movement equals closing. Cash and bank
accounts are taken from the Cash & Cash Equivalents Chart of Accounts category
(and any default cash/bank accounts in Accounting Settings). Transfers between
cash/bank accounts are excluded so they do not inflate activity totals.

Bank statement matching and clearance history live under **Accounting → Bank
Reconciliation** (Print/PDF from that screen), not as a separate report menu item.

## Sales Report
Overall sales performance, with the ability to reconcile against payments received.

## Order / POS Reports
Order Detail, Product-wise Sales, Variation-wise Sales, Customer-wise Sales,
Branch-wise Sales, Order Source Sales, Payment Method Sales, Order Status,
Cancelled Orders, Due / Credit Sales, Discount Report, Order Tax Report,
Top Selling, Offline Orders, and Order Correction Report. These live under
the Orders menu and respect the same Print / PDF / Excel / CSV permission
split as every other report.

The **Order Correction Report** shows every same-day manager correction of a
posted POS sale: which order, who corrected it, their reason, and the order's
total before and after. A **View Changes** action opens a full before/after
comparison (line items, quantities, prices, and payments) for that
correction, straight off the Activity Log entry the correction created - see
[Activity Log](12-audit-security.md#activity-log).

## Customer Reports
Customer Ledger, Customer Aging (how overdue customer balances are), Customer
Payment History.

## Procurement Reports
Supplier Ledger, Supplier Aging, Accounts Payable, Supplier Payment History,
Purchase Return Summary/Detail.

## Inventory Reporting System
Under **Inventory → Reports** (four sub-sections):

- **Stock Reports** — Stock Summary/Availability/Low Stock, Stock Ledger/Product
  Ledger, Valuation, Aging/Slow-Fast-Non-Moving, Transfer, Reconciliation &
  Adjustment, Loss/Wastage/Damage, Batch/Lot & Expiry.
- **Consumption Reports** (Manufacturing module) — Material Consumption Analysis
  with grouping and Expected vs Actual variance.
- **Manufacturing Reports** — Plan report; Production summary/cost/yield/
  variance/wastage-proxy/traceability.
- **Recipe/BOM Reports** — Recipe lines, cost analysis, material requirement,
  coverage.

Manufacturing operational screens (Recipes/BOM, Manufacturing Plans,
Productions) are a submenu inside Inventory, not a separate top-level menu,
so production stays tied to stock flow the same way its reports are.

## Service Management Reports
Service Sale Report, Service Purchase Report, Service Payment Report, Service
Transaction Summary.

## HR & Payroll Reports
A full set covering Employee (master/directory/joining and more), Attendance
(summary and detail), Leave, and Employee Lifecycle reports, plus an HR Dashboard;
and — where Payroll is enabled — Payroll Summary, Monthly Payroll Register,
Payroll Cost, Salary Slip, and Department Payroll Cost reports.

Every report respects your role's permissions — for example, a role can be given
access to view a report without being able to export or print it, and Payroll
financial reports are deliberately kept separate from general HR reports so you can
grant broad HR visibility without exposing salary figures.
