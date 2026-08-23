# Human Resources & Payroll

HRM and Payroll are **separately toggleable** on your subscription package — a
business can have HRM without Payroll (e.g. to track staff and attendance only), or
both together.

## Organization Structure

Set up **Departments**, **Designations** (job titles), and **Shifts** before adding
employees, so each employee can be assigned to the right structure.

## Employees

The **Employee** record holds personal details, employment info, and documents. An
employee can optionally be given a login (with the **Employee** role) to use
**Self-Service** — see below.

## Attendance & Leave

- **Attendance** is tracked per employee per day (check-in/out), either recorded by
  HR or self-reported through Self-Service.
- **Leave Types** (e.g. annual, sick, unpaid) and **Leave Requests** support a
  request → approval workflow.

## Salary Structure

Define reusable **Salary Components** (earnings and deductions, e.g. basic salary,
housing allowance, tax deduction), then assign each employee an
**Salary Structure** built from those components. An employee can have multiple
structure versions over time (e.g. after a raise) — only the current one is active.

## Running Payroll

A **Payroll Run** is generated for a given month/branch. For each employee, the
system calculates their payslip automatically from:

- their active salary structure (basic salary + structure earnings + structure
  deductions),
- **overtime**,
- a **per-day rate** (basic salary ÷ days in the month), used to calculate
  deductions for **unpaid leave days** and **absent days**,
- any **ad-hoc deductions** active for that month,
- and any **active advances** — each advance recovers its configured installment
  amount (or whatever balance remains, if smaller) directly from that month's pay.

**Net Pay = (Basic + Structure Earnings + Overtime) − (Structure Deductions +
Ad-hoc Deductions + Unpaid Leave Deduction + Absent Deduction + Advance Recovery)**

Once generated, a payroll run can be **finalized**, **paid**, or **reopened** if a
correction is needed. Individual **Payslips** can be viewed and printed at any time.

## Advances, Deductions & the Employee Ledger

**Employee Advances** (salary advances, recovered over installments through
payroll) and one-off **Employee Deductions** are tracked per employee, with every
movement rolling into a running **Employee Ledger** so you always know an
employee's outstanding balance.

## Resignation & Exit

**Employee Exit** (resignation or termination) starts an **Exit Clearance**
workflow — final settlement, asset return, and sign-off before the employee's
record is closed out.

## Assets

Track company **Assets** (laptops, phones, tools) and their **Allocation** to
employees, including issue and return dates, so you always know who has what.

## Employee Self-Service (ESS)

Employees with an Employee-role login get their own portal to check in/out, view
their own attendance and leave history, apply for leave, view/download their own
payslips, request an advance, submit a resignation, and view their profile — with
no visibility into anyone else's data or business administration screens.
