# Jobs, Commands & Scheduling

## Console Commands (`app/Console/Commands`)

| Command | Signature | Schedule | Purpose |
|---|---|---|---|
| `ProcessFixedAssetDepreciationCommand` | `fixed-assets:post-depreciation {--dry-run} {--business=} {--date=}` | daily 00:15, `withoutOverlapping()` | Posts due straight-line depreciation for active Fixed Assets and related JVs (idempotent per period) |
| `ProcessSubscriptionLifecycleCommand` | `subscriptions:process-lifecycle {--dry-run}` | daily 01:00 | Advances tenant subscription states (trial→expired, due-renewal reminders) |
| `ProcessAccountingPeriodsCommand` | `accounting-periods:process {--dry-run} {--business=}` | daily 02:00, `withoutOverlapping()` | Auto-opens/closes fiscal accounting periods per business |
| `CheckNotificationAlertsCommand` | `notifications:check-alerts {--dry-run}` | hourly | Scans for conditions that should raise in-app notifications |
| `ProcessRecurringTransactionsCommand` | `recurring-transactions:process {--dry-run} {--id=}` | hourly | Generates due journal entries from Recurring Transaction templates |
| `BackfillBarcodesCommand` | `barcode:backfill {--business=} {--dry-run} {--force}` | manual/ops only | One-off backfill of missing product-variation barcodes (also exposed via `POST admin/product/barcode/backfill`) |
| `BackupAutoRunCommand` | `backups:auto-run` | hourly, `withoutOverlapping()` | Checks `BackupSetting` and runs a scheduled backup + retention cleanup if one is due - see [Backup, Restore & Disaster Recovery](19-backup-restore.md) |

All scheduled commands are registered in `app/Console/Kernel.php`. Run
`php artisan schedule:run` (typically via cron/Task Scheduler) to fire due
commands, or invoke any command directly for ops/debugging.

## Queued Jobs (`app/Jobs`)

| Job | Purpose |
|---|---|
| `GenerateSubscriptionInvoicePdfJob` | Renders and saves a subscription invoice PDF asynchronously to `public_path('uploads/subscription_invoices')`, `$tries = 3`, `$backoff = 10` |
| `SendPurchaseRequestQuotationJob` | Generates an RFQ/quotation PDF and sends it to one supplier per job (so one slow/failing send doesn't block the others) |
| `SendSubscriptionNotificationJob` | Dispatches one subscription notification (expiry reminder or lifecycle event) to a business |
| `ProcessBroadcastNotificationJob` | Sends one batch of FCM broadcast recipients (`ShouldBeUnique` per campaign), then re-dispatches until complete/cancelled |

Job pattern: constructor takes just an ID, re-fetches the model inside `handle()`,
wraps rendering in try/catch and logs+rethrows on failure. Use this pattern for any
new async, per-document PDF generation; for on-demand single-page PDFs, follow the
synchronous controller `stream()` pattern instead (see
[Reports Infrastructure](06-reports-infrastructure.md)).

FCM broadcast details: [FCM Broadcast Notifications](13-fcm-broadcast-notifications.md).

The queue must be running (`php artisan queue:work`) for any of the above to
actually process — see [Development Setup](01-dev-setup.md).
