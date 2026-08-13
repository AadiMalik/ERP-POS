<?php

namespace App\Console\Commands;

use App\Enums\Status;
use App\Enums\SubscriptionEventType;
use App\Jobs\SendSubscriptionNotificationJob;
use App\Models\BusinessSubscription;
use App\Models\SubscriptionReminderLog;
use App\Models\SubscriptionSetting;
use App\Services\Concrete\Admin\SubscriptionHistoryService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionLifecycleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-lifecycle {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transitions subscription statuses through grace period/expiry and dispatches configured expiry reminders. Safe to re-run (reminder dedupe is enforced at the database level).';

    protected SubscriptionHistoryService $history_service;

    public function __construct(SubscriptionHistoryService $history_service)
    {
        parent::__construct();
        $this->history_service = $history_service;
    }

    public function handle()
    {
        $dry_run = (bool) $this->option('dry-run');
        $settings = SubscriptionSetting::current();

        $this->transitionExpiredSubscriptions($settings, $dry_run);
        $this->dispatchReminders($settings, $dry_run);

        $this->info('Subscription lifecycle processing complete.');

        return 0;
    }

    protected function transitionExpiredSubscriptions(SubscriptionSetting $settings, bool $dry_run): void
    {
        $now = now();

        // active/trial/payment_pending subscriptions that just passed end_at
        // enter the grace period.
        $entering_grace = BusinessSubscription::whereIn('status', [Status::TRIAL, Status::ACTIVE, Status::PAYMENT_PENDING])
            ->whereNotNull('end_at')
            ->where('end_at', '<', $now)
            ->whereNull('grace_period_ends_at')
            ->where('is_deleted', 0)
            ->get();

        foreach ($entering_grace as $subscription) {
            try {
                $grace_end = Carbon::parse($subscription->end_at)->addDays((int) $settings->default_grace_period_days);

                $this->line("Grace period: {$subscription->business_subscription_id} until {$grace_end}");

                if ($dry_run) {
                    continue;
                }

                $from_status = $subscription->status;

                $subscription->update([
                    'status' => Status::GRACE_PERIOD,
                    'grace_period_ends_at' => $grace_end,
                    'date_updated' => now(),
                ]);

                $this->history_service->log(
                    $subscription->business_id,
                    $subscription->business_subscription_id,
                    SubscriptionEventType::GRACE_PERIOD_ENTERED,
                    $from_status,
                    Status::GRACE_PERIOD,
                    null,
                    null,
                    null,
                    null,
                    null
                );
            } catch (\Throwable $e) {
                Log::error('Subscription grace-period transition failed for ' . $subscription->business_subscription_id . ': ' . $e->getMessage());
            }
        }

        // grace_period subscriptions past their grace end become expired.
        $expiring = BusinessSubscription::where('status', Status::GRACE_PERIOD)
            ->whereNotNull('grace_period_ends_at')
            ->where('grace_period_ends_at', '<', $now)
            ->where('is_deleted', 0)
            ->get();

        foreach ($expiring as $subscription) {
            try {
                $this->line("Expiring: {$subscription->business_subscription_id}");

                if ($dry_run) {
                    continue;
                }

                $subscription->update([
                    'status' => Status::EXPIRED,
                    'date_updated' => now(),
                ]);

                if ($subscription->business) {
                    $subscription->business->update(['status' => Status::EXPIRED]);
                }

                $this->history_service->log(
                    $subscription->business_id,
                    $subscription->business_subscription_id,
                    SubscriptionEventType::EXPIRED,
                    Status::GRACE_PERIOD,
                    Status::EXPIRED,
                    null,
                    null,
                    null,
                    null,
                    null
                );
            } catch (\Throwable $e) {
                Log::error('Subscription expiry transition failed for ' . $subscription->business_subscription_id . ': ' . $e->getMessage());
            }
        }
    }

    protected function dispatchReminders(SubscriptionSetting $settings, bool $dry_run): void
    {
        $thresholds = $settings->reminder_thresholds_days ?: [30, 15, 7, 3, 1];
        $now = now();

        $candidates = BusinessSubscription::whereIn('status', [Status::TRIAL, Status::ACTIVE])
            ->whereNotNull('end_at')
            ->where('end_at', '>=', $now)
            ->where('is_deleted', 0)
            ->get();

        foreach ($candidates as $subscription) {
            $days_remaining = (int) floor($now->diffInDays($subscription->end_at, false));

            foreach ($thresholds as $threshold) {
                if ($days_remaining !== (int) $threshold) {
                    continue;
                }

                try {
                    if ($dry_run) {
                        $this->line("Would remind: {$subscription->business_subscription_id} at {$threshold} day(s)");
                        continue;
                    }

                    // Unique index on (business_subscription_id, threshold_days,
                    // channel) is the actual duplicate-send guard: this insert
                    // only succeeds once per threshold, safe under re-runs.
                    SubscriptionReminderLog::create([
                        'subscription_reminder_log_id' => generateUuid(),
                        'business_id' => $subscription->business_id,
                        'business_subscription_id' => $subscription->business_subscription_id,
                        'threshold_days' => $threshold,
                        'channel' => 'email',
                        'status' => 'sent',
                        'sent_at' => now(),
                        'date_created' => now(),
                    ]);

                    SendSubscriptionNotificationJob::dispatch(
                        $subscription->business_subscription_id,
                        'reminder',
                        (int) $threshold
                    );

                    $this->line("Reminder dispatched: {$subscription->business_subscription_id} at {$threshold} day(s)");
                } catch (QueryException $e) {
                    // Duplicate key - already sent for this threshold, skip silently.
                    continue;
                } catch (\Throwable $e) {
                    Log::error('Subscription reminder dispatch failed for ' . $subscription->business_subscription_id . ': ' . $e->getMessage());
                }
            }
        }
    }
}
