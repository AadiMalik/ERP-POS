<?php

namespace App\Console\Commands;

use App\Models\BackupSetting;
use App\Services\Concrete\Admin\BackupService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackupAutoRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backups:auto-run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs a scheduled backup + retention cleanup if BackupSetting says one is due. Scheduled hourly in Kernel so the Super Admin can change frequency/time from the UI without redeploying.';

    protected BackupService $backup_service;

    public function __construct(BackupService $backup_service)
    {
        parent::__construct();
        $this->backup_service = $backup_service;
    }

    public function handle()
    {
        $setting = BackupSetting::current();

        if (! $setting->is_scheduled_enabled) {
            return 0;
        }

        if (! $this->isDue($setting)) {
            return 0;
        }

        $this->info('Running scheduled backup...');
        $log = $this->backup_service->createManualBackup('scheduled');
        $this->backup_service->runCleanup();

        $setting->update(['last_run_at' => now()]);

        $this->info('Scheduled backup finished with status: ' . $log->status);

        return $log->status === 'success' ? 0 : 1;
    }

    protected function isDue(BackupSetting $setting): bool
    {
        $now = Carbon::now();
        [$hour, $minute] = array_map('intval', explode(':', $setting->run_time ?: '02:00'));

        $scheduledToday = $now->copy()->setTime($hour, $minute);
        if ($now->lt($scheduledToday)) {
            return false;
        }

        if ($setting->frequency === 'weekly' && $now->dayOfWeek !== (int) ($setting->day_of_week ?? 0)) {
            return false;
        }

        if ($setting->frequency === 'monthly' && $now->day !== (int) ($setting->day_of_month ?? 1)) {
            return false;
        }

        if (! $setting->last_run_at) {
            return true;
        }

        return match ($setting->frequency) {
            'weekly' => $setting->last_run_at->diffInDays($now) >= 6,
            'monthly' => $setting->last_run_at->diffInDays($now) >= 27,
            default => ! $setting->last_run_at->isSameDay($now),
        };
    }
}
