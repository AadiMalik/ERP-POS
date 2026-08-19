<?php

namespace App\Support\Recurring\Generators;

use App\Enums\RecurringRunStatus;
use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Shared run-recording logic for concrete generators (ExpenseRecurringGenerator,
 * JournalEntryRecurringGenerator, ...). The (recurring_transaction_id, run_date)
 * unique index on recurring_transaction_runs is the hard idempotency backstop -
 * a duplicate insert here throws, which the caller's own catch block turns
 * into a "failed" record rather than a silent double-generation.
 */
abstract class AbstractRecurringGenerator implements RecurringTransactionGenerator
{
    protected function recordRun(
        string $runId,
        RecurringTransaction $rt,
        Carbon $runDate,
        string $status,
        ?string $generatedModelType,
        ?string $generatedModelId,
        ?string $errorMessage,
        string $triggeredBy,
        ?int $triggeredByUserId,
        Carbon $startedAt
    ): RecurringTransactionRun {
        return RecurringTransactionRun::create([
            'recurring_transaction_run_id' => $runId,
            'recurring_transaction_id'     => $rt->recurring_transaction_id,
            'run_date'                     => $runDate->toDateString(),
            'status'                       => $status,
            'generated_model_type'         => $generatedModelType,
            'generated_model_id'           => $generatedModelId,
            'error_message'                => $status === RecurringRunStatus::FAILED ? $errorMessage : null,
            'triggered_by'                 => $triggeredBy,
            'triggered_by_user_id'         => $triggeredByUserId ?? Auth::id(),
            'started_at'                   => $startedAt,
            'completed_at'                 => now(),
            'createdby_id'                 => $triggeredByUserId ?? Auth::id(),
            'date_created'                 => now(),
        ]);
    }
}
