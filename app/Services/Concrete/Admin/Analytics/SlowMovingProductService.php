<?php

namespace App\Services\Concrete\Admin\Analytics;

use App\Services\Concrete\Admin\Reports\Inventory\StockAgingReportService;
use Illuminate\Support\Collection;

/**
 * Slow-moving products - NOT a new metric. StockAgingReportService already
 * computes this exact classification via its 'velocity' report_mode
 * (movement_class: fast/slow/non_moving, derived from
 * product_variation_stock_transactions). This is a thin filter wrapper, no
 * new SQL - kept as its own class only so AnalyticsService has one widget
 * method per concern, matching the sibling new-metric services.
 */
class SlowMovingProductService
{
    public function __construct(protected StockAgingReportService $aging_service)
    {
    }

    public function build(array $obj): Collection
    {
        $obj['report_mode'] = 'velocity';

        return $this->aging_service->build($obj)
            ->whereIn('movement_class', ['slow', 'non_moving'])
            ->sortByDesc('days_idle')
            ->values();
    }
}
