<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\BackupLog;
use App\Traits\Auditable;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Restore is deliberately NOT provided by spatie/laravel-backup (too
 * destructive for a generic package to automate). This service:
 *  1. verifies the chosen backup's integrity,
 *  2. takes a "pre_restore" safety backup of the CURRENT state first (via
 *     BackupService) so an accidental/bad restore is itself recoverable,
 *  3. restores the database from the archive's db-dumps/*.sql via the mysql
 *     client (credentials passed through a --defaults-extra-file, never on
 *     the command line or in a log),
 *  4. restores only the known "data" directories (storage/app/public,
 *     public/uploads) - never application code, which lives in version
 *     control and isn't part of the restore blast radius.
 */
class BackupRestoreService
{
    use Auditable;

    protected BackupService $backupService;

    /** Only these relative paths are ever written back to disk from a restore archive. */
    protected array $restorableDataPaths = [
        'storage/app/public',
        'public/uploads',
    ];

    public function __construct(BackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    public function restore(string $backupLogId, int $userId): array
    {
        $log = BackupLog::where('backup_log_id', $backupLogId)->where('is_deleted', 0)->where('status', 'success')->firstOrFail();

        $verification = $this->backupService->verifyBackup($log);
        if (! $verification['ok']) {
            $this->logActivity('backup', $log->backup_log_id, 'restore_failed', null, null, "Restore aborted, backup failed verification: {$verification['message']}");
            throw new Exception('Cannot restore: ' . $verification['message']);
        }

        $safety = $this->backupService->createManualBackup('pre_restore', $userId);
        if ($safety->status !== 'success') {
            $this->logActivity('backup', $log->backup_log_id, 'restore_failed', null, null, 'Restore aborted: pre-restore safety backup failed, current data was not touched.');
            throw new Exception('Restore aborted: could not create a safety backup of the current state first. Nothing was changed.');
        }

        $disk = explode(',', $log->disk)[0];
        $tempDir = storage_path('app/backup-temp/restore-' . uniqid());
        File::makeDirectory($tempDir, 0755, true);

        try {
            $zipPath = $tempDir . '/archive.zip';
            file_put_contents($zipPath, Storage::disk($disk)->get($log->file_path));

            $extractDir = $tempDir . '/extracted';
            $zip = new ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new Exception('Could not open backup archive for extraction.');
            }
            $zip->extractTo($extractDir);
            $zip->close();

            $this->restoreDatabase($extractDir);
            $filesRestored = $this->restoreFiles($extractDir);

            $this->logActivity(
                'backup',
                $log->backup_log_id,
                'restored',
                null,
                ['restored_paths' => $filesRestored, 'safety_backup_id' => $safety->backup_log_id],
                "Restored from backup {$log->file_name}. Safety backup {$safety->file_name} was created first."
            );

            $this->notifySuperAdmins(
                'Restore completed',
                "System restored from backup {$log->file_name}. A safety backup ({$safety->file_name}) of the prior state was created automatically.",
                $log
            );

            return [
                'ok' => true,
                'safety_backup' => $safety,
                'restored_paths' => $filesRestored,
            ];
        } catch (Throwable $e) {
            // ActivityLog.description is a short `string` column - keep the
            // full message only in the exception rethrown below / the
            // notification's `text` message column.
            $shortError = \Illuminate\Support\Str::limit($e->getMessage(), 140, '...');
            $this->logActivity('backup', $log->backup_log_id, 'restore_failed', null, null, "Restore failed: {$shortError} Safety backup {$safety->file_name} is available.");
            $this->notifySuperAdmins('Restore FAILED', "Restore from backup {$log->file_name} failed: {$e->getMessage()}. A safety backup ({$safety->file_name}) of the state before the attempt is available.", $log);

            throw new Exception('Restore failed: ' . $e->getMessage() . ' A safety backup of your data from just before this attempt is available in the backup list.');
        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    protected function restoreDatabase(string $extractDir): void
    {
        $dumpFiles = File::glob($extractDir . '/db-dumps/*.sql');
        if (empty($dumpFiles)) {
            throw new Exception('No database dump found inside the backup archive.');
        }
        $dumpFile = $dumpFiles[0];

        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        $credentialsFile = tempnam(sys_get_temp_dir(), 'restore_cnf_');
        file_put_contents($credentialsFile, implode(PHP_EOL, [
            '[client]',
            "user = '{$config['username']}'",
            "password = '{$config['password']}'",
            "host = '{$config['host']}'",
            "port = '{$config['port']}'",
        ]));

        try {
            $quote = PHP_OS_FAMILY === 'Windows' ? '"' : "'";
            $binary = $this->resolveBinary('mysql');

            $command = sprintf(
                '%s%s%s --defaults-extra-file="%s" "%s" < "%s"',
                $quote,
                $binary,
                $quote,
                $credentialsFile,
                $config['database'],
                $dumpFile
            );

            $process = Process::fromShellCommandline($command, null, null, null, 900);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new Exception('Database import failed: ' . $process->getErrorOutput());
            }
        } finally {
            @unlink($credentialsFile);
        }
    }

    protected function resolveBinary(string $name): string
    {
        $dir = config('database.connections.mysql.dump.dumpBinaryPath', '');

        return $dir !== '' ? rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name : $name;
    }

    protected function restoreFiles(string $extractDir): array
    {
        $restored = [];

        foreach ($this->restorableDataPaths as $relativePath) {
            $source = $extractDir . '/' . $relativePath;
            if (! File::isDirectory($source)) {
                continue;
            }

            $destination = base_path($relativePath);
            File::ensureDirectoryExists(dirname($destination));
            File::copyDirectory($source, $destination);
            $restored[] = $relativePath;
        }

        return $restored;
    }

    protected function notifySuperAdmins(string $title, string $message, BackupLog $log): void
    {
        app(NotificationDispatchService::class)->dispatch(
            'backup_restore',
            null,
            null,
            $title,
            $message,
            'backup_log',
            $log->backup_log_id,
            route('backups.index'),
            null,
            'backup_restore:' . $log->backup_log_id . ':' . now()->timestamp,
            [RoleNames::SUPERADMIN]
        );
    }
}
