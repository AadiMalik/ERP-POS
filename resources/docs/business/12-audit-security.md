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
approving, rejecting, exporting, or importing a record — is recorded with **who**
did it, **when**, and **what changed**. Filter by business, module, or action type
to investigate a specific change or keep a general audit trail for compliance.

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

## Who Can See What

Access to the Activity Log and Login History is itself permission-gated, so you
can give an auditor or manager visibility into "what happened" without giving them
the ability to change anything.
