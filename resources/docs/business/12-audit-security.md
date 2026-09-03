# Notifications, Activity Log & Security

## Notifications

The bell icon shows in-app alerts relevant to you — e.g. low-stock warnings,
items awaiting your approval, or subscription reminders. Mark individual alerts or
all of them as read, and control which alerts you receive under
[Settings](10-settings.md).

Admins can send marketing push notifications to mobile customers under
**Push Notifications** (Firebase Settings, Templates, Broadcasts). See
[Push Notifications (FCM)](13-push-notifications.md).

## Activity Log

Every significant action across the system — creating, updating, deleting,
approving, rejecting, voiding, cancelling, posting/unposting, disposing, exporting,
or importing a record — is recorded with **who** did it, **when**, **which
business/branch**, and **what changed** (previous vs. new values, plus a reason
where one was entered). Filter by business, module, action type, user, or a
specific record ID to investigate a specific change or keep a general audit
trail for compliance. The Module and Action filter lists always reflect the
actions actually being logged, so they stay current as new modules start
recording activity.

Sale **void** and **cancellation** are each logged under the Orders module with
their own distinct action (**voided** / **cancelled**) rather than a generic
status change, so they can be filtered and reported on directly. The same
applies to sale returns/refunds, purchase returns, stock counts (see below),
journal voucher posting/unposting, customer/supplier payment posting/reversal,
POS register open/close/void, and fixed asset transfer/depreciation/disposal —
each of these records both the before and after state.

**Stock Taking** (the current stock-count/adjustment workflow) logs the count
itself — created, updated, or deleted, with the counted quantities and total
variance — in addition to its approval/rejection, so a discrepancy can always be
traced back to who entered it and when, not just who approved it.

Same-day **POS order corrections** are logged under the Orders module with action
**corrected**, including the original and new line/payment totals, the manager who
authorized it, the reason, and the permission used (`order.correct`). The order's
Status History also records each correction. For a manager-facing view scoped just
to corrections - how many orders were corrected, by whom, and a before/after
comparison of each one - see the **Order Correction Report** under
[Reports](09-reports.md#order--pos-reports).

The Activity Log is append-only: there is no screen or action anywhere in the
system to edit or delete an existing entry, so it cannot be tampered with by a
normal user, an accountant, or even a Business Admin.

## Login History

A record of sign-in activity is kept for security review — useful for spotting
unusual access to an account.

## Password Reset

Forgotten passwords are reset with a one-time email code (valid for 10 minutes),
not a long-lived link. The system first checks that the email is registered; if
it is not, it returns an error and does not send a code.

- **Admin / Dukanaz login** — the email is Dukanaz-branded (logo, name, colors)
  and greets you by name.
- **Website / storefront** — signup, sign-in, and password-reset codes use the
  business logo, name, and website theme colors, with **Powered by Dukanaz**
  only in the footer. Password reset also requires the email to already belong
  to a customer of that business.

After a successful reset you sign in again with the new password.

## File Uploads

Logo and image uploads on Business, Branch, Supplier, Category, and Brand
screens accept only image files (`jpg`, `jpeg`, `png`, `webp`) up to 2MB.
Subscription and order payment proofs accept image or PDF files up to 5MB.
Expense and customer-payment attachments accept PDF, images, and common
Office documents (`pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx`) up to 5MB.

## Who Can See What

Access to the Activity Log and Login History is itself permission-gated, so you
can give an auditor or manager visibility into "what happened" without giving them
the ability to change anything.
