<?php

namespace App\Support\Recurring\Generators;

use App\Models\RecurringTransaction;
use App\Models\RecurringTransactionRun;
use Carbon\Carbon;

/**
 * Contract for a single recurring transaction type's generation logic.
 * Implementations must never let an expected/validatable failure (missing or
 * inactive account/category, unbalanced journal lines, etc.) escape as an
 * uncaught exception - they catch it internally and return a `status=failed`
 * RecurringTransactionRun with a clear error_message instead, so the
 * scheduler command can keep processing the rest of the due schedules and
 * the failure is fully traceable.
 */
interface RecurringTransactionGenerator
{
    public function generate(
        RecurringTransaction $rt,
        Carbon $runDate,
        string $triggeredBy,
        ?int $triggeredByUserId
    ): RecurringTransactionRun;
}
