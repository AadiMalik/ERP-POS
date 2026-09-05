<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\BackupLog;
use App\Models\BackupSetting;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

/**
 * Wraps spatie/laravel-backup (backup:run) with:
 *  - a BackupLog row per attempt (so failed/incomplete backups are visible,
 *    not silently missing from the disk listing spatie itself would show),
 *  - post-run zip integrity verification instead of trusting the command's
 *    exit code alone,
 *  - runtime disk selection driven by BackupSetting (so the Super Admin can
 *    change storage destinations from the UI without editing config files),
 *  - retention/max-storage cleanup mapped directly to the two settings the
 *    admin screen exposes (retention_days, max_storage_mb), and
 *  - in-app Super Admin notifications + ActivityLog audit entries.
 */
class BackupService
{
    use Auditable;

    protected NotificationDispatchService $notifier;

    public function __construct(NotificationDispatchService $notifier)
    {
        $this->notifier = $notifier;
    }

    /**
     * Disks the Super Admin is allowed to pick, filtered to ones that are
     * actually usable (no hard-coded credentials - s3 only appears once its
     * env vars are filled in).
     */
    public function availableDisks(): array
    {
        $disks = ['backups' => 'Local (Private Server Storage)'];

        if (config('filesystems.disks.s3.key') && config('filesystems.disks.s3.secret') && config('filesystems.disks.s3.bucket')) {
            $disks['s3'] = 'Amazon S3 / Object Storage';
        }

        return $disks;
    }

    protected function resolveDisks(?array $requested): array
    {
        $available = array_keys($this->availableDisks());
        $disks = array_values(array_intersect($requested ?: ['backups'], $available));

        return $disks ?: ['backups'];
    }

    public function createManualBackup(string $type, ?int $userId = null): BackupLog
    {
        $setting = BackupSetting::current();
        $disks = $this->resolveDisks($setting->disks);

        config(['backup.backup.destination.disks' => $disks]);
        config(['backup.monitor_backups.0.disks' => $disks]);
        if ($setting->max_storage_mb) {
            config(['backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than' => $setting->max_storage_mb]);
        }

        $log = BackupLog::create([
            'backup_log_id' => generateUuid(),
            'type' => $type,
            'status' => 'running',
            'disk' => implode(',', $disks),
            'includes_database' => true,
            'includes_files' => true,
            'started_at' => now(),
            'initiated_by' => $userId,
            'is_deleted' => 0,
            'date_created' => now(),
        ]);

        $primaryDisk = $disks[0];
        $before = collect(Storage::disk($primaryDisk)->allFiles())->flip();

        try {
            $exitCode = Artisan::call('backup:run', ['--disable-notifications' => true]);
            $output = Artisan::output();

            $newFile = collect(Storage::disk($primaryDisk)->allFiles())
                ->reject(fn ($f) => $before->has($f))
                ->sortDesc()
                ->first();

            if ($exitCode !== 0 || ! $newFile) {
                throw new Exception($output ?: 'backup:run failed without producing an archive.');
            }

            $verification = $this->verifyZip($primaryDisk, $newFile);
            if (! $verification['ok']) {
                throw new Exception('Backup verification failed: ' . $verification['message']);
            }

            $size = Storage::disk($primaryDisk)->size($newFile);
            $checksum = hash('sha256', Storage::disk($primaryDisk)->get($newFile));

            $log->update([
                'status' => 'success',
                'file_path' => $newFile,
                'file_name' => basename($newFile),
                'size_bytes' => $size,
                'checksum_sha256' => $checksum,
                'finished_at' => now(),
            ]);

            $this->logActivity('backup', $log->backup_log_id, 'created', null, ['type' => $type, 'size_bytes' => $size], "Backup ({$type}) created successfully.");
            $this->notifySuperAdmins('backup_success', 'Backup completed', "A {$type} backup ({$log->file_name}) completed successfully.", $log);
        } catch (Throwable $e) {
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            // ActivityLog.description is a short `string` column - the full
            // error (e.g. a Process exception's command output) is already
            // preserved in full on BackupLog.error_message (a `text` column).
            $shortError = \Illuminate\Support\Str::limit($e->getMessage(), 180, '...');
            $this->logActivity('backup', $log->backup_log_id, 'failed', null, null, "Backup ({$type}) failed: {$shortError}");
            $this->notifySuperAdmins('backup_failed', 'Backup failed', "A {$type} backup failed: {$shortError}", $log);
        }

        return $log->fresh();
    }

    /**
     * Opens the archive and confirms it isn't truncated/corrupted, and (when
     * the backup was supposed to include the database) that it actually
     * contains a database dump - so a partial/corrupt archive is reported
     * as failed instead of success.
     */
    protected function verifyZip(string $disk, string $path): array
    {
        $isLocal = config("filesystems.disks.{$disk}.driver") === 'local';
        $localPath = $isLocal ? Storage::disk($disk)->path($path) : null;

        $tempCopy = null;
        if (! $localPath) {
            $tempCopy = tempnam(sys_get_temp_dir(), 'backup_verify_');
            file_put_contents($tempCopy, Storage::disk($disk)->get($path));
            $localPath = $tempCopy;
        }

        try {
            $zip = new ZipArchive();
            if ($zip->open($localPath) !== true) {
                return ['ok' => false, 'message' => 'Archive could not be opened (corrupted or incomplete).'];
            }

            if ($zip->numFiles === 0) {
                $zip->close();
                return ['ok' => false, 'message' => 'Archive is empty.'];
            }

            $hasDbDump = false;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                // Zip entries are written with the OS-native separator (spatie
                // uses DIRECTORY_SEPARATOR when building the archive), so
                // normalize before comparing.
                if (str_starts_with(str_replace('\\', '/', $zip->getNameIndex($i)), 'db-dumps/')) {
                    $hasDbDump = true;
                    break;
                }
            }
            $zip->close();

            if (! $hasDbDump) {
                return ['ok' => false, 'message' => 'Archive does not contain a database dump.'];
            }

            return ['ok' => true, 'message' => 'OK'];
        } finally {
            if ($tempCopy) {
                @unlink($tempCopy);
            }
        }
    }

    public function verifyBackup(BackupLog $log): array
    {
        if ($log->status !== 'success' || ! $log->file_path) {
            return ['ok' => false, 'message' => 'This backup did not complete successfully.'];
        }

        $disk = explode(',', $log->disk)[0];

        if (! Storage::disk($disk)->exists($log->file_path)) {
            return ['ok' => false, 'message' => 'Backup file is missing from storage.'];
        }

        $result = $this->verifyZip($disk, $log->file_path);

        if ($result['ok'] && $log->checksum_sha256) {
            $current = hash('sha256', Storage::disk($disk)->get($log->file_path));
            if (! hash_equals($log->checksum_sha256, $current)) {
                return ['ok' => false, 'message' => 'Checksum mismatch - file has changed since it was created.'];
            }
        }

        return $result;
    }

    public function listBackups()
    {
        $setting = BackupSetting::current();

        return BackupLog::where('is_deleted', 0)
            ->orderByDesc('started_at')
            ->get()
            ->map(function (BackupLog $log) use ($setting) {
                $disk = $log->disk ? explode(',', $log->disk)[0] : null;
                $log->file_missing = $log->status === 'success' && $disk && ! Storage::disk($disk)->exists($log->file_path);
                $log->retention_expires_at = $log->date_created ? Carbon::parse($log->date_created)->addDays($setting->retention_days ?? 30) : null;
                $log->is_expired = $log->retention_expires_at ? $log->retention_expires_at->isPast() : false;
                $log->can_download = $log->status === 'success' && ! $log->file_missing && $disk === 'backups';

                return $log;
            });
    }

    public function deleteBackup(string $id, ?int $userId = null): void
    {
        $log = BackupLog::where('backup_log_id', $id)->where('is_deleted', 0)->firstOrFail();

        foreach (explode(',', $log->disk ?? '') as $disk) {
            if ($disk && $log->file_path && Storage::disk($disk)->exists($log->file_path)) {
                Storage::disk($disk)->delete($log->file_path);
            }
        }

        $log->update(['is_deleted' => 1]);

        $this->logActivity('backup', $log->backup_log_id, 'deleted', null, null, 'Backup deleted by Super Admin.');
    }

    public function downloadPath(string $id): array
    {
        $log = BackupLog::where('backup_log_id', $id)->where('is_deleted', 0)->where('status', 'success')->firstOrFail();
        $disk = explode(',', $log->disk)[0];

        if ($disk !== 'backups' || ! Storage::disk($disk)->exists($log->file_path)) {
            throw new Exception('This backup cannot be downloaded (stored on a remote disk or missing).');
        }

        $this->logActivity('backup', $log->backup_log_id, 'downloaded', null, null, 'Backup downloaded by Super Admin.');

        return [$disk, $log->file_path, $log->file_name];
    }

    /**
     * Retention cleanup mapped straight to the two admin-facing settings:
     * age (retention_days) and total size (max_storage_mb). The most recent
     * successful backup is never deleted, mirroring spatie's own
     * DefaultStrategy safety rule.
     */
    public function runCleanup(): array
    {
        $setting = BackupSetting::current();
        $deleted = [];

        $successful = BackupLog::where('is_deleted', 0)->where('status', 'success')->orderByDesc('date_created')->get();
        $newestId = optional($successful->first())->backup_log_id;

        foreach ($successful as $log) {
            if ($log->backup_log_id === $newestId) {
                continue;
            }
            if (Carbon::parse($log->date_created)->addDays($setting->retention_days ?? 30)->isPast()) {
                $this->deleteBackup($log->backup_log_id);
                $deleted[] = $log->file_name;
            }
        }

        if ($setting->max_storage_mb) {
            $remaining = BackupLog::where('is_deleted', 0)->where('status', 'success')->orderBy('date_created')->get();
            $totalBytes = $remaining->sum('size_bytes');
            $limitBytes = $setting->max_storage_mb * 1024 * 1024;

            foreach ($remaining as $log) {
                if ($totalBytes <= $limitBytes || $remaining->count() <= 1) {
                    break;
                }
                if ($log->backup_log_id === $newestId) {
                    continue;
                }
                $totalBytes -= $log->size_bytes;
                $this->deleteBackup($log->backup_log_id);
                $deleted[] = $log->file_name;
            }
        }

        if ($deleted) {
            $this->logActivity('backup', null, 'cleanup', null, ['deleted' => $deleted], 'Expired/over-limit backups cleaned up.');
        }

        return $deleted;
    }

    protected function notifySuperAdmins(string $type, string $title, string $message, BackupLog $log): void
    {
        $this->notifier->dispatch(
            $type,
            null,
            null,
            $title,
            $message,
            'backup_log',
            $log->backup_log_id,
            route('backups.index'),
            ['status' => $log->status],
            $type . ':' . $log->backup_log_id,
            [RoleNames::SUPERADMIN]
        );
    }
}
