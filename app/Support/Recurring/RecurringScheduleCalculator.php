<?php

namespace App\Support\Recurring;

use App\Enums\RecurringFrequency;
use App\Models\RecurringTransaction;
use Carbon\Carbon;

/**
 * Pure scheduling math shared by RecurringTransactionService (seed
 * next_run_date on create, "next run" preview for the create/edit form) and
 * ProcessRecurringTransactionsCommand (advance next_run_date after each run).
 * No I/O, no DB access - everything is derived from the RecurringTransaction
 * attributes and a reference date.
 */
class RecurringScheduleCalculator
{
    /**
     * The first occurrence date matching the schedule, on or after start_date.
     */
    public static function initialRunDate(RecurringTransaction $rt): Carbon
    {
        $start = Carbon::parse($rt->start_date)->startOfDay();

        switch ($rt->frequency) {
            case RecurringFrequency::DAILY:
                return $start;

            case RecurringFrequency::WEEKLY:
                if ((int) $start->dayOfWeek === (int) $rt->weekday) {
                    return $start;
                }
                return self::nextRunDate($rt, $start->copy()->subDay());

            case RecurringFrequency::MONTHLY:
                $target = $start->copy()->day(min((int) $rt->day_of_month, $start->daysInMonth));
                if ($target->gte($start)) {
                    return $target;
                }
                return self::nextRunDate($rt, $start);

            case RecurringFrequency::YEARLY:
                $target = Carbon::create($start->year, (int) $rt->month_of_year, 1)
                    ->day(min((int) $rt->day_of_month, Carbon::create($start->year, (int) $rt->month_of_year, 1)->daysInMonth));
                if ($target->gte($start)) {
                    return $target;
                }
                return self::nextRunDate($rt, $start);

            default:
                return $start;
        }
    }

    /**
     * The next occurrence strictly after $from.
     */
    public static function nextRunDate(RecurringTransaction $rt, Carbon $from): Carbon
    {
        $from = $from->copy()->startOfDay();

        switch ($rt->frequency) {
            case RecurringFrequency::DAILY:
                return $from->copy()->addDay();

            case RecurringFrequency::WEEKLY:
                return $from->copy()->next((int) $rt->weekday);

            case RecurringFrequency::MONTHLY:
                // Always re-derive the target day from the stored day_of_month,
                // never from $from->day - otherwise a Feb clamp to the 28th
                // would permanently shift every subsequent month to the 28th.
                $next = $from->copy()->startOfDay()->addMonthNoOverflow();
                return $next->day(min((int) $rt->day_of_month, $next->daysInMonth));

            case RecurringFrequency::YEARLY:
                $next = Carbon::create($from->year + 1, (int) $rt->month_of_year, 1);
                return $next->day(min((int) $rt->day_of_month, $next->daysInMonth));

            default:
                return $from->copy()->addDay();
        }
    }

    /**
     * N upcoming occurrences from a given date (inclusive), for the create/edit
     * form's "next run" preview and RecurringTransactionService::previewNextRun().
     */
    public static function upcoming(RecurringTransaction $rt, int $count = 5): array
    {
        $dates = [];
        $current = self::initialRunDate($rt);

        for ($i = 0; $i < $count; $i++) {
            if ($rt->end_date && $current->gt(Carbon::parse($rt->end_date))) {
                break;
            }
            $dates[] = $current->copy();
            $current = self::nextRunDate($rt, $current);
        }

        return $dates;
    }
}
