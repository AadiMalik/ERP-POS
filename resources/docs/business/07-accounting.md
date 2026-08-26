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

See [Reports](09-reports.md) for the full set of financial statements (Trial
Balance, General Ledger, Profit & Loss, Balance Sheet, Day Book, Tax Report, and
more) generated from this data.
