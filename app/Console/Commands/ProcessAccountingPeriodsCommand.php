<?php

namespace App\Console\Commands;

use App\Enums\RoleNames;
use App\Models\AccountingPeriod;
use App\Models\AccountingSetting;
use App\Models\Business;
use App\Models\User;
use App\Services\Concrete\Admin\FiscalYearService;
use App\Services\Concrete\Admin\PeriodClosingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Daily driver for the Simple-mode "Accounting Period Closing =
 * Monthly/Yearly" setting: tops up upcoming AccountingPeriod rows and, for
 * every business whose current OPEN period has fully elapsed, runs the same
 * PeriodClosingService checklist the Advanced Mode manual "Close" button
 * uses. If issues are found, the period is left open and the issues are
 * recorded for the Accounting Period screen to surface - never closed
 * silently with unresolved problems. Structured like
 * ProcessRecurringTransactionsCommand: per-business try/catch so one
 * tenant's failure doesn't stop the run.
 */
class ProcessAccountingPeriodsCommand extends Command
{
    protected $signature = 'accounting-periods:process {--dry-run} {--business=}';

    protected $description = 'Tops up upcoming accounting periods and attempts to auto-close elapsed ones for businesses on Monthly/Yearly closing. Safe to re-run.';

    public function __construct(protected FiscalYearService $fiscal_year_service, protected PeriodClosingService $closing_service)
    {
        parent::__construct();
    }

    public function handle()
    {
        $dry_run = (bool) $this->option('dry-run');
        $only_business_id = $this->option('business');

        $businesses = Business::whereHas('accountingSetting', function ($q) {
                $q->where('enable_accounting', 1)->whereIn('period_closing_mode', ['monthly', 'yearly']);
            })
            ->when($only_business_id, fn ($q) => $q->where('business_id', $only_business_id))
            ->get();

        $this->info("Found {$businesses->count()} business(es) with automatic period closing enabled.");

        foreach ($businesses as $business) {
            try {
                $actor_id = $this->resolveActorUserId($business);

                if ($dry_run) {
                    $this->line("[{$business->name}] would top up future periods and check the current open period for closing.");
                    continue;
                }

                Auth::onceUsingId($actor_id);

                $this->fiscal_year_service->ensureFuturePeriods($business);

                $open_period = AccountingPeriod::where('business_id', $business->business_id)
                    ->where('is_deleted', 0)
                    ->where('status', 'open')
                    ->whereDate('end_date', '<', today())
                    ->orderBy('end_date')
                    ->first();

                if (!$open_period) {
                    continue;
                }

                $attempt = $this->closing_service->attemptClose($open_period, 'scheduler', $actor_id);

                $this->line("[{$business->name}] period \"{$open_period->name}\" closing attempt: {$attempt->result}");
            } catch (Throwable $e) {
                Log::error('accounting-periods:process failed for ' . $business->business_id . ': ' . $e->getMessage());
                $this->error("[{$business->name}] failed: " . $e->getMessage());
            }
        }

        $this->info('Accounting period processing complete.');

        return 0;
    }

    /**
     * Falls back to a Business Admin of the business, since there is no
     * "schedule creator" for this business-wide job - services called from
     * here must tolerate Auth::id() being null.
     */
    protected function resolveActorUserId(Business $business): ?int
    {
        return User::role(RoleNames::BUSINESSADMIN)
            ->where('business_id', $business->business_id)
            ->where('is_deleted', 0)
            ->value('id');
    }
}
