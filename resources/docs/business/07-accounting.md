# Accounting & Bookkeeping

## Chart of Accounts

Your books are organized around a **Chart of Accounts** — a tree of Account Types
(Asset, Liability, Equity, Income, Expense), Sub-Types, and individual Accounts
(which can have parent/child accounts, e.g. "Bank" as a parent with individual bank
accounts underneath). A default chart of accounts can be applied from Accounting
Settings to get started quickly.

Set **Customer Account** and **Supplier Account** in Accounting Settings before
taking credit sales or posting customer/supplier payments. New customers (admin
or website signup) and new suppliers receive that COA automatically; changing the
default and saving settings also updates existing customers/suppliers.

When you **post a customer payment**, the receivable account is chosen in this
order: the customer's own linked Chart of Account (on their customer profile)
first; if that is missing or no longer valid, the **Customer Account** default
from Accounting Settings; if neither is configured, the payment is rejected and
nothing is posted to the books.

## How Transactions Post Automatically

Most day-to-day transactions post to the books **automatically** — you don't need
to manually create a journal entry for a normal sale, purchase receipt, or expense:
- Completing a **POS sale** posts revenue and (where inventory is tracked)
  cost-of-goods-sold entries.
- Receiving goods via a **Good Receipt Note** posts the corresponding
  purchase/inventory entry the moment stock is physically received — not when the
  purchase order is merely raised.
- **Expenses**, **Supplier Payments**, and **Customer Payments** each post their
  own entries.

Every automatic entry is tagged back to the transaction that created it, so you can
always trace a journal entry to its source document.

## Manual Journals & Vouchers

For anything that isn't an automatic transaction, **Journal Entries** let you post
manually, and **Vouchers** (Cash Payment, Bank Payment, Cash Receipt, Bank Receipt,
and other standard voucher types) provide guided forms for the most common manual
postings.

## Bank Reconciliation

**Bank Reconciliation** (under Accounting) lets you match your bank statement to
what the ERP already recorded for a cash or bank Chart of Accounts account — it does
**not** create new accounting entries.

Typical workflow:
1. Start a reconciliation: pick the bank/cash account, period, and enter the
   **bank statement closing balance** (opening can default from the last completed
   reconciliation).
2. Import statement lines from CSV/Excel (`date`, `amount`, `reference`,
   `description` — positive amount = money in, negative = money out) or add lines
   manually.
3. Match each statement line to an ERP journal line (same amount). Prior uncleared
   items from earlier dates also appear so they can be cleared when they show on
   the bank statement. Use **Suggest Matches** for candidates based on amount,
   date (±3 days), and reference; confirm each match yourself.
4. Watch the **Difference** panel: Statement Closing − Adjusted Book. Adjusted Book
   is the ERP balance as of the period end after treating unmatched ERP deposits/
   withdrawals as not yet on the bank statement. Complete only when the difference
   is **0.00**.
5. Completed reconciliations are read-only history (who completed them and when).
   You can **Reopen** a completed session to edit matches if needed. Ignore a
   statement-only line (e.g. a bank fee not yet booked) until you post it in the
   books and match it later.

Print or PDF the reconciliation summary for your records. Use the Cash & Bank
Ledger report for a continuous ledger view of the same accounts.

## Recurring Transactions

Set up a **Recurring Transaction** template (e.g. monthly rent) and the system will
automatically generate the journal entry on schedule, without you re-entering it
each month. Templates can be paused and resumed.

## Fiscal Years & Period Closing

Define **Fiscal Years** split into **Accounting Periods**. Once a period is
**closed**, no new transaction can post into it — this protects finalized books
from accidental changes after you've reported on them. Closing can require
resolving pending issues first, or be manually forced with a reason; a closed
period can be **reopened** (also with a reason) if genuinely necessary. Periods can
also be configured to open/close automatically on a schedule.

## Budgets

Set **Budgets** per account/period and compare against actuals via the Budget
Variance report to track over/under spending.

## Expenses

Two expense flows exist: **Expenses** tied to a POS register session (day-to-day
till expenses), and **Admin Expenses** for general business expenses not tied to a
specific POS session — both categorized under **Expense Categories**.

## Fixed Assets & Depreciation

Accounting **Fixed Assets** (buildings, machinery, vehicles, equipment) are managed
separately from HR **Assets** (employee-issued laptops/phones). Use **Fixed Asset
Categories** and **Fixed Assets** under Accounting.

Before recording assets, configure these accounts in **Settings → Accounting**:
- Fixed Asset / Purchase Asset Account
- Accumulated Depreciation Account
- Depreciation Expense Account
- Gain on Asset Disposal Account
- Loss on Asset Disposal Account

When you create an asset directly in Fixed Assets, the system posts an acquisition
journal voucher using those accounts. If the asset was already purchased through
the **Purchase** module (and that purchase already posted accounting), tick
**Purchase already posted** (or link the purchase) so a duplicate JV is not created.

Depreciation uses **straight-line** by default, with frequency Daily / Weekly /
Monthly / Yearly. You can optionally increase or decrease the period amount over
time, and set a minimum book-value percentage (default 0%) so depreciation never
goes below residual/minimum value. Purchase cost is kept separate from current
book value.

Use **Accounting → Depreciation** to view all depreciation entries for your
business, post a period manually for an active asset, or reverse the latest
entry if needed. A scheduled job also runs every day at **00:15** and posts due
depreciation with `Dr Depreciation Expense / Cr Accumulated Depreciation`. You
can pause, resume, or post depreciation from the asset detail screen as well.

Reports: **Fixed Asset Register**, **Depreciation Report**, **Asset Valuation /
Book Value**, and **Asset Disposal / Sale** under Accounting Reports.

See [Reports](09-reports.md) for the full set of financial statements (Trial
Balance, General Ledger, Profit & Loss, Balance Sheet, Cash Flow Statement, Day
Book, Tax Report, and more) generated from this data.
