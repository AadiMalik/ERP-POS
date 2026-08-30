# Subscription & Billing

Your business's access to the system is governed by a **subscription** to a
**package** (plan). The package determines which modules you can use (POS,
Inventory, Accounting, HRM, Payroll, Service Management) and any usage limits
(e.g. maximum branches, products, or warehouses).

## My Subscription

Under **My Subscription**, a Business Admin can:
- View the current plan, its limits, and when it renews or expires.
- Toggle **Monthly** / **Yearly** to compare the matching catalog packages.
- Compare available plans on the **Plans & Pricing** cards (list price, any
  discount %, charged price, included modules, and key limits such as branches,
  users, warehouses, products, customers, and employees).
- Request an **upgrade**, **downgrade**, or **renewal** of the current plan
  from those cards. The billing period comes from the selected package
  (monthly package vs yearly package). Submitting a request creates an unpaid
  invoice for Super Admin to confirm.
- View and pay **invoices**, and download them as PDF.
- Submit a **payment** against an invoice for the platform operator to approve.

## Upgrading or Downgrading

Each pricing card shows whether it is your current plan and offers
**Upgrade**, **Downgrade**, or **Request Renewal**. Only one request can be
open at a time.

**Downgrade is blocked if you already use more than the lower plan allows.**
You will see a message listing each item that is over the new limit, for
example: products 80 used, plan allows 50 (remove 30). Reduce those records
first (delete or archive extras), then you can submit the downgrade request.

The same check applies if a higher-priced plan still has a tighter cap, or if
the lower plan does not include a module you already use (for example HRM
employees when moving to a plan without HRM).

## Renewal & Payment Approval

When you renew or register with a package, an unpaid invoice is created
immediately (marked as **New** or **Renew**). Super Admin reviews it on the
**Subscription Invoices** screen, where they can:

- Confirm or reject the payment (once decided, the other action is locked)
- Print / download the invoice PDF
- Delete the invoice (which also removes related payments)

Until payment is confirmed:
- **New** registrations keep business status **pending** (or **under review**)
  and have **no** subscription end date yet.
- **Renewals** keep the previous expiry date (and an expired business stays
  expired) until confirmation.

On confirmation the business becomes **active**, dates are applied, a
confirmation email with the invoice PDF is sent (using platform email
settings), and any open renewal request is marked approved.

## What Happens If a Subscription Lapses

If a subscription isn't renewed in time, the business moves through defined
lifecycle states and eventually **expired**. An expired (or still-pending)
Business Admin can still log in, but the admin sidebar shows only **My
Subscription** (and Profile) so they can renew. Full menus return after
payment is confirmed and the subscription is active again.

You'll also receive reminders as your subscription approaches its expiry date.

## Intro website & Contact inquiries

Public registration from the intro website creates a pending business and an
unpaid **New** invoice for Super Admin. From **Intro CMS → Contact Inquiries**,
Super Admin can also register a business from a contact message, attach payment
details, update status, and activate (confirm payment) in one place.
