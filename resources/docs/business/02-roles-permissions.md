# Roles, Permissions & Team Access

## How Access Control Works

Every screen and every action in the system — viewing a report, creating a purchase,
deleting a product, downloading a PDF — is protected by a **permission**. A **role**
is simply a named bundle of permissions (e.g. "Accountant" bundles all the
accounting-related permissions). Every user is assigned exactly one role, and that
role's permissions decide what they can see and do.

This means access control is precise: you can give someone view-only access to
Reports without letting them create or delete anything, or let a manager approve
purchase requests without giving them access to Payroll.

## Built-in Roles

Two roles are **global templates** managed by the platform and reset automatically
whenever the system's permission set changes:

- **Super Admin** — the platform operator. Manages all businesses, packages, and
  subscriptions. Automatically has every permission in the system, including two
  platform-only controls no business can see: **Business Access Control**
  (enabling/disabling a business's access to ERP, Website & Mobile App, POS, or
  Offline POS — see [The Wider Platform](14-platform-ecosystem.md)) and
  **System Feature Controls** (platform-wide on/off switches for integrations
  like push notifications or online payment gateways).
- **Business Admin** — the owner/top administrator of a single business. Has every
  permission available to a business (everything except platform-level actions like
  managing other businesses or raw permission definitions).

Every business also gets a starting set of **role templates** it can freely edit or
delete, covering common positions: Branch Admin, General Manager, Operation Manager,
Inventory Manager, Finance Manager, Sale Manager, Purchase Manager, Marketing
Manager, Accountant, HR Manager, Reporting Analyst, Staff, Employee, Order Taker,
and POS Manager. Each starts with a sensible default permission set for that role
(for example, a Reporting Analyst starts with read-only access to reports; an
Accountant starts with the full accounting toolkit).

## Customizing Roles

From **Roles**, a Business Admin can:
- Edit any role's permission set with a module-by-module checklist.
- Create entirely new custom roles for positions specific to your business.
- **Reset** a role back to its default permission set if it's been changed and you
  want to start over.

Permissions are grouped by module in the Role Create/Edit screen (e.g. all Sales
permissions together, all HRM permissions together) so you can quickly see and
adjust what a role can do.

## Adding Team Members

Add people under **Admin Users**, assign them a role and (if applicable) a branch.
A user tied to a branch typically only operates within that branch's data (e.g. a
Branch Admin or POS Manager). Business-level roles (General Manager, Accountant,
etc.) see data across all branches.

## Employee Self-Service

Employees added through the HRM module (see
[Human Resources & Payroll](08-hrm-payroll.md)) can be given a login with the
**Employee** role, which grants access only to the Self-Service portal — checking
in/out, viewing their own leave history, payslips, and profile, and submitting leave,
advance, or resignation requests for approval — without any access to other staff's
data or to business administration screens.

## Activity Log

Every significant action (create, update, delete, approve, export, etc.) is recorded
in the **Activity Log** with who did it and when, so you always have an audit trail.
See [Notifications, Activity Log & Security](12-audit-security.md).
