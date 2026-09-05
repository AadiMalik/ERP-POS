# Backup, Restore & Disaster Recovery

Super-Admin-only module (`superadmin` route-group middleware +
`permission:backup.*`). Wraps `spatie/laravel-backup` (^8.2) for archive
creation and adds everything the package deliberately doesn't provide:
attempt-level audit logging, post-run integrity verification, admin-configurable
schedule/retention, and a restore procedure.

## Files

| Layer | File |
|---|---|
| Controller | `app/Http/Controllers/Admin/BackupController.php` |
| Services | `app/Services/Concrete/Admin/BackupService.php`, `BackupRestoreService.php` |
| Models | `app/Models/BackupLog.php`, `app/Models/BackupSetting.php` |
| Scheduled command | `app/Console/Commands/BackupAutoRunCommand.php` (`backups:auto-run`, hourly) |
| Views | `resources/views/admin/backups/{index,settings}.blade.php` |
| Config | `config/backup.php` (spatie), `config/database.php` (`connections.mysql.dump`), `config/filesystems.php` (`backups` disk) |
| Migrations | `2026_09_05_120000_create_backup_logs_table.php`, `2026_09_05_120001_create_backup_settings_table.php` |

## Routes (prefix `admin/backups`, all `superadmin`-gated)

| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/` | `backups.index` | `backup.view` |
| POST | `/` | `backups.store` | `backup.create` |
| GET | `settings` | `backup-settings.edit` | `backup.manage` |
| POST | `settings` | `backup-settings.update` | `backup.manage` |
| POST | `cleanup` | `backups.cleanup` | `backup.manage` |
| GET | `{id}/download` | `backups.download` | `backup.download` |
| DELETE | `{id}` | `backups.destroy` | `backup.delete` |
| POST | `{id}/restore` | `backups.restore` | `backup.restore` |

Permissions are registered in `PermissionRegistry` under the `backup` module
key, all `is_system = true` (platform-level, matches `package`/`business`/
`subscription`). Super Admin gets them automatically via
`RoleDefaultPermissions::defaultsForRole()` → `PermissionRegistry::allNames()`.

## What a backup contains

`config/backup.php`:
- `source.databases` → `['mysql']` (the app's only connection).
- `source.files.include` → `base_path()`, `exclude` → `vendor`, `node_modules`,
  `.git`, `tests`, `storage/framework`, `storage/logs`, `storage/app/backups`,
  `storage/app/backup-temp`, `storage/app/temp`, `storage/app/import-export`,
  `storage/app/imports` (all regenerable/temporary, not application data).
- `source.files.relative_path` is set to `base_path()` (not `null`) so the
  zip stores app-relative paths (`storage/app/public/...`,
  `public/uploads/...`) instead of absolute/drive-letter paths -
  **`BackupRestoreService` depends on this** to safely extract the archive
  back into the app tree.
- `destination.disks` default is `['backups']`, but `BackupService` overrides
  `config('backup.backup.destination.disks')` and
  `config('backup.monitor_backups.0.disks')` at runtime from
  `BackupSetting::current()->disks` before every run, so the Super Admin can
  change storage destinations from the UI without touching this file.
- `notifications.notifications` is intentionally empty - this project's own
  `NotificationDispatchService` handles success/failure alerts to Super Admins
  instead of spatie's mail-based notifications, so backups work without any
  mail configuration.
- `password`/`encryption`: optional archive encryption via
  `BACKUP_ARCHIVE_PASSWORD`. **Leave that env var completely unset** (not set
  to an empty string) when encryption is not wanted -
  `Spatie\Backup\Listeners\EncryptBackupArchive::shouldEncrypt()` checks for
  `null`, and an empty string is truthy-enough to trigger encryption with a
  blank password, corrupting the archive.

`config/database.php` → `connections.mysql.dump`:
- `dumpBinaryPath` = `env('MYSQLDUMP_PATH')` - the directory containing
  `mysqldump`/`mysql`, needed on Windows/XAMPP where those binaries aren't on
  PATH (set in `.env` to the local XAMPP `mysql/bin` directory with a trailing
  slash). Leave empty where `mysqldump` is already on PATH.
- `excludeTables` = `['backup_logs', 'backup_settings']` - **required**. The
  `backup_logs` row for a backup is updated from `running` → `success` only
  *after* the mysqldump is taken, so without this exclusion, restoring any
  backup would revert that backup's own row (and any other backup bookkeeping
  since) back to `running`/stale state, even though the actual archive files
  on disk are completely unaffected. Excluding these two tables keeps the
  dashboard's own audit trail immune to the restores it performs.

Backups intentionally include `.env` (only Super Admin can ever reach a
backup file) since it's required to fully restore the application, and
`storage/app/public` for the same reason as `public/uploads` - both hold
business-uploaded files, not code.

## `BackupService`

`createManualBackup(string $type, ?int $userId)` (`$type` is `manual`,
`scheduled`, or `pre_restore`):
1. Resolves destination disks from `BackupSetting`, filtered to disks that are
   actually usable (`availableDisks()` - `backups` is always available, `s3`
   only if `AWS_ACCESS_KEY_ID`/`AWS_SECRET_ACCESS_KEY`/`AWS_BUCKET` are set -
   this is how "no hard-coded credentials" is enforced: an unconfigured cloud
   disk simply never appears as a choice).
2. Inserts a `BackupLog` row with `status = running` *before* calling
   `Artisan::call('backup:run', ['--disable-notifications' => true])`, so a
   crash mid-run still leaves a visible `running`/never-finished row rather
   than nothing.
3. Diffs the disk's file listing before/after to find the new archive (spatie
   doesn't return the path from `Artisan::call`).
4. **Verifies** the archive before ever marking it `success`:
   `verifyZip()` re-opens it with `ZipArchive`, confirms it's non-empty, and
   confirms it contains a `db-dumps/` entry when a database was expected.
   Zip entries use the OS-native separator (`\` on Windows), so the check
   normalizes to `/` before comparing.
5. Records `size_bytes` and a `sha256` checksum of the whole archive.
6. On any failure (verification, exception, non-zero exit code, no new file),
   marks the log `failed` with `error_message` - **never reports success for
   an incomplete backup**.
7. Logs to `ActivityLog` via `App\Traits\Auditable` (`module = 'backup'`) and
   notifies all Super Admins in-app via `NotificationDispatchService`
   (`dedupe_key` includes the log id, so it fires once per attempt).

`verifyBackup(BackupLog $log)` - re-checks integrity + checksum on demand
(used before every restore).

`listBackups()` - returns non-deleted `BackupLog` rows annotated with
`file_missing` (row says success but the file is gone from disk),
`retention_expires_at`/`is_expired` (from `BackupSetting::retention_days`),
and `can_download` (local disk + file present).

`deleteBackup($id)` - deletes the underlying file(s) from every disk the
backup was written to, then soft-deletes the row (`is_deleted = 1`, kept for
audit history) and logs to `ActivityLog`.

`downloadPath($id)` - only permits download when the backup's primary disk is
`backups` (local); cloud-stored backups must be retrieved from that
provider's console, which is also enforced by `can_download` in the UI.

`runCleanup()` - retention logic mapped directly to the two settings the
admin screen exposes (not spatie's tiered `DefaultStrategy`, for a 1:1 mapping
to the UI): deletes successful backups older than `retention_days`, then - if
`max_storage_mb` is set - deletes the oldest remaining backups until under the
limit. **The single most recent successful backup is never deleted**, mirroring
spatie's own safety rule. Called by `BackupController::cleanup()` (manual
"Run Cleanup" button) and by `BackupAutoRunCommand` after every scheduled run.

## `BackupRestoreService`

Restore is not provided by spatie/laravel-backup - it's too destructive for a
generic package to automate, so it's implemented here deliberately narrowly:

1. **Verify** the target backup (`BackupService::verifyBackup`) - refuses to
   restore a corrupted/incomplete archive.
2. **Safety backup first, always.** Calls
   `BackupService::createManualBackup('pre_restore', $userId)` before touching
   anything. If that safety backup doesn't succeed, the restore is aborted
   and nothing is changed - this is the core "accidental restore doesn't
   permanently destroy current data" guarantee from the requirements.
3. Downloads the archive to a scratch dir under `storage/app/backup-temp/`
   and extracts it with `ZipArchive`.
4. **Database restore**: locates `db-dumps/*.sql` in the extracted tree, then
   shells out to the `mysql` client the same way spatie/db-dumper shells out
   to `mysqldump` for backups - a `--defaults-extra-file` temp credentials
   file (`[client]` section with user/password/host/port, same format spatie
   uses, deleted in a `finally` block) so **the DB password never appears on
   the command line, in `ps`, or in any log**. Runs via
   `Symfony\Component\Process\Process::fromShellCommandline()` with `<` input
   redirection (same approach the underlying `spatie/db-dumper` package
   already uses and that is proven to work on this Windows/XAMPP setup).
5. **File restore is intentionally narrow**: only
   `storage/app/public` and `public/uploads` (declared in
   `$restorableDataPaths`) are ever copied back from the archive, overwriting
   the live directories. Application code is never touched by a restore - it
   lives in version control, so restoring it from a zip would be redundant
   and riskier than restoring only the two directories that hold
   business-uploaded data.
6. Logs the outcome (success or failure) to `ActivityLog` and notifies Super
   Admins. On failure, the error message explicitly points back at the safety
   backup created in step 2 so an admin knows how to undo a bad attempt.
7. The scratch directory is always removed in a `finally` block.

The confirmation flow (`BackupController::restore`) requires the request body
field `confirm_phrase` to equal exactly `RESTORE` (enforced server-side via
validation, not just the UI's typed-confirmation box), in addition to the
`backup.restore` permission and `superadmin` middleware.

## Scheduling (`BackupAutoRunCommand`, `backups:auto-run`)

Registered **hourly** in `Kernel::schedule()` (not by frequency), because the
actual frequency/time/day is admin-configurable at runtime via
`BackupSetting` rather than fixed in `Kernel.php`. Each hourly tick:
1. No-ops if `BackupSetting::current()->is_scheduled_enabled` is false.
2. `isDue()` checks the current time against `run_time`, and for
   weekly/monthly frequencies also `day_of_week`/`day_of_month`, and that
   `last_run_at` isn't already within the current period (same-day for daily,
   ≥6 days for weekly, ≥27 days for monthly).
3. If due: runs `createManualBackup('scheduled')`, then `runCleanup()`, then
   updates `last_run_at`.

This design means changing the schedule in the Settings screen takes effect
on the very next hourly tick - no redeploy or cron edit required.

## Database schema

`backup_logs` (uuid PK `backup_log_id`): `type` (manual/scheduled/pre_restore),
`status` (running/success/failed), `disk` (comma-joined disk names actually
written to), `file_path`, `file_name`, `size_bytes`, `checksum_sha256`,
`includes_database`, `includes_files`, `error_message`, `started_at`,
`finished_at`, `initiated_by` (nullable user id - null for scheduled runs),
`is_deleted` (soft-delete, keeps deleted backups in the audit trail),
`date_created`.

`backup_settings` (uuid PK `backup_setting_id`, single current row like
`SubscriptionSetting`/`InventorySetting`): `is_scheduled_enabled`,
`frequency`, `run_time`, `day_of_week`, `day_of_month`, `retention_days`,
`max_storage_mb`, `disks` (json array), `last_run_at`.

Both tables are excluded from the database dump itself (see
`config/database.php` above).

## Disaster-recovery scenarios & admin recovery procedure

**Database corruption / accidental bad data change** - restore the most
recent healthy backup from the dashboard. A pre-restore safety backup is
taken automatically first.

**Accidental/incorrect restore** - restore the `pre_restore` safety backup
that was created automatically immediately before the bad restore (it appears
in the dashboard with Type = "Pre Restore", timestamped just before the
incident).

**A scheduled or manual backup job fails** - it is recorded as `Failed` with
an `error_message` (never silently reported as success), and every Super
Admin gets an in-app notification. Check `error_message` in the dashboard row
or the corresponding `ActivityLog` entry (`module = backup`, `action =
failed`) for the cause (common causes: `mysqldump`/`mysql` not found - check
`MYSQLDUMP_PATH` in `.env` - or insufficient disk space, below).

**Insufficient disk space** - `backup:run` (and therefore
`createManualBackup`) throws, which is caught and recorded as a `failed`
`BackupLog` with the underlying error message, and a failure notification is
sent - it never reports success for a partial/truncated archive because
`verifyZip()` re-opens and checks the result before marking it successful.
Configuring `max_storage_mb` and a reasonable `retention_days` in Backup
Settings keeps the backup disk itself from being the cause.

**Corrupted backup file (bit rot, incomplete upload/copy)** - detected by
`verifyBackup()`/`verifyZip()`, which every restore calls before touching
anything; a corrupted backup cannot be restored and is reported as
`file_missing`/failed verification in the UI rather than silently used.

**Storage (disk) failure on the primary backup destination** - configure a
second disk (`s3`, once `AWS_*` env vars are set) in Backup Settings; new
backups are written to every configured disk in the same run, so a single
disk's failure doesn't lose the newest backup. Existing local-only backups
are unaffected by an S3 outage and vice versa.

**Full server loss** - since `.env`, `storage/app/public`, and
`public/uploads` are all inside the archive alongside the database dump, a
backup stored on a disk that survives the incident (i.e. not `backups`/local)
is sufficient to rebuild the application from a fresh checkout of the
codebase (application code itself is recovered from version control, not
from the backup archive) plus that one archive's database dump and data
directories.
