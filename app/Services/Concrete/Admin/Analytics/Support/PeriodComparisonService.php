<?php

namespace App\Services\Concrete\Admin\Analytics\Support;

use Carbon\Carbon;

/**
 * One reusable period-over-period comparison helper - every Analytics widget
 * that needs a trend arrow calls this instead of duplicating "shift the date
 * range back and re-run the same query" logic per metric.
 */
class PeriodComparisonService
{
    /**
     * @param  callable(Carbon $start, Carbon $end): array  $fetcher  Returns
     *         a flat array of numeric (and/or non-numeric) values for the
     *         given range - called once for the current range, once for the
     *         immediately preceding range of equal length.
     */
    public function compare(Carbon $start, Carbon $end, callable $fetcher): array
    {
        $days = $start->diffInDays($end) + 1;
        $previous_end = $start->copy()->subDay()->endOfDay();
        $previous_start = $previous_end->copy()->subDays($days - 1)->startOfDay();

        $current = $fetcher($start->copy(), $end->copy());
        $previous = $fetcher($previous_start, $previous_end);

        return [
            'current' => $current,
            'previous' => $previous,
            'previous_range' => ['start' => $previous_start, 'end' => $previous_end],
            'deltas' => $this->deltas($current, $previous),
        ];
    }

    /**
     * Percent change per numeric key. A previous value of 0 is treated as
     * +100% growth when the current value is positive, 0% when both are 0 -
     * never a division-by-zero or an infinite/NAN delta.
     */
    protected function deltas(array $current, array $previous): array
    {
        $deltas = [];

        foreach ($current as $key => $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $prev = (float) ($previous[$key] ?? 0);
            $deltas[$key] = $prev == 0.0
                ? ((float) $value > 0 ? 100.0 : 0.0)
                : round((((float) $value - $prev) / $prev) * 100, 1);
        }

        return $deltas;
    }
}
