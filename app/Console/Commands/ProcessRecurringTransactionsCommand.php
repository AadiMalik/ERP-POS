<?php

namespace App\Console\Commands;

use App\Enums\RecurringTriggeredBy;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\RecurringTransaction;
use App\Models\User;
use App\Services\Concrete\Admin\RecurringTransactionService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Checks every active recurring transaction schedule whose next_run_date has
 * arrived and generates the underlying transaction (Expense, Journal Entry -
 * more types plug in via App\Support\Recurring\Generators\RecurringGeneratorRegistry).
 * Structured like CheckNotificationAlertsCommand: per-schedule try/catch so
 * one bad template doesn't stop the rest of the run, safe to re-run (the
 * (recurring_transaction_id, run_date) unique index on
 * recurring_transaction_runs plus a row lock in
 * RecurringTransactionService::executeSchedule() prevent duplicate generation).
 */
class ProcessRecurringTransactionsCommand extends Command
{
    protected $signature = 'recurring-transactions:process {--dry-run} {--id=}';

    protected $description = 'Generates due recurring transactions (Expense, Journal Entry, ...) from active schedules. Safe to re-run.';

    public function __construct(protected RecurringTransactionService $service)
    {
        parent::__construct();
    }

    public function handle()
    {
        $dry_run = (bool) $this->option('dry-run');
        $only_id = $this->option('id');

        $due = RecurringTransaction::where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->whereNotNull('next_run_date')
            ->where('next_run_date', '<=', today()->toDateString())
            ->when($only_id, fn ($q) => $q->where('recurring_transaction_id', $only_id))
            ->get();

        $this->info("Found {$due->count()} due recurring transaction(s).");

        foreach ($due as $rt) {
            if ($dry_run) {
                $this->line("Would generate [{$rt->transaction_type}] \"{$rt->name}\" for {$rt->next_run_date->toDateString()}");
                continue;
            }

            try {
                $actor_id = $this->resolveActorUserId($rt);

                Auth::onceUsingId($actor_id);

                $run = $this->service->executeSchedule($rt, Carbon::parse($rt->next_run_date), RecurringTriggeredBy::SCHEDULER, $actor_id);

                $this->line("[{$rt->name}] run {$run->status}" . ($run->error_message ? ': ' . $run->error_message : ''));
            } catch (Throwable $e) {
                Log::error('recurring-transactions:process failed for ' . $rt->recurring_transaction_id . ': ' . $e->getMessage());
                $this->error("[{$rt->name}] failed: " . $e->getMessage());
            }
        }

        $this->info('Recurring transaction processing complete.');

        return 0;
    }

    /**
     * Prefer the schedule's creator (if still an active user), else fall back
     * to a Business Admin of the same business, else null - generators/services
     * called from here must tolerate Auth::id() being null.
     */
    protected function resolveActorUserId(RecurringTransaction $rt): ?int
    {
        if ($rt->createdby_id) {
            $creator = User::where('id', $rt->createdby_id)->where('is_deleted', 0)->first();
            if ($creator) {
                return $creator->id;
            }
        }

        return User::role(RoleNames::BUSINESSADMIN)
            ->where('business_id', $rt->business_id)
            ->where('is_deleted', 0)
            ->value('id');
    }
}
