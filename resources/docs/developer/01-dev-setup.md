# Development Setup

## Local Environment

This project runs under **XAMPP**, with the document root at `htdocs` (not
`htdocs/erp/public`). The app is reached via a root-level `index.php`/`.htaccess`
bridge at `c:\xampp\htdocs\erp\`.

- **Correct local URL:** `http://localhost/erp/`
- **Do not** use `http://localhost/erp/public/` or `php artisan serve` for browser
  testing — both resolve `asset()`/`url()` calls to the wrong base path (missing the
  `/erp` segment), so all vendor JS/CSS silently 404 and pages render unstyled.

## Environment Configuration

Standard Laravel `.env` — database (MySQL via XAMPP), mail, queue connection, and
the custom settings surfaced under [Configuration Reference](10-configuration.md)
(`config/theme_presets.php`, `config/print_defaults.php`,
`config/thermal_print_defaults.php`, `config/barcode_label_defaults.php`).

## Useful Artisan Commands

```bash
php artisan migrate                              # run pending migrations
php artisan db:seed --class=PermissionSeeder      # sync permission rows from PermissionRegistry (see CLAUDE.md)
php artisan route:list --name=<filter>            # inspect registered routes
php artisan queue:work                            # process queued jobs (PDF generation, notifications, quotation sends)
php artisan schedule:run                          # run due scheduled commands (normally via cron/Task Scheduler)
```

## Queue & Scheduler

Several features depend on the queue worker running: subscription invoice PDF
generation (`GenerateSubscriptionInvoicePdfJob`), purchase-request quotation
sending (`SendPurchaseRequestQuotationJob`), and subscription notifications
(`SendSubscriptionNotificationJob`). See
[Jobs, Commands & Scheduling](09-jobs-commands.md) for the full list and schedule.

## Seeders

`PermissionSeeder` is the **single source of truth** for permission rows — it reads
`App\Support\Permissions\PermissionRegistry` and
`App\Support\Permissions\RoleDefaultPermissions` and syncs the database. Whenever
either of those files changes, re-run:
```bash
php artisan db:seed --class=PermissionSeeder
```
Do not write new one-off "seed permission" migrations — add to the Registry instead
(see [Permissions & Access Control](05-permissions-access-control.md)).

## Test Login (local only)

A seeded Super Admin account exists for local testing:
`admin@admin.com` / `1234` (lands on `/admin/subscriptions`, the Super Admin default
page, rather than `/home`).
