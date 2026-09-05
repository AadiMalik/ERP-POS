<?php

namespace Tests\Feature\Analytics;

use App\Services\Concrete\Admin\Analytics\Support\PeriodComparisonService;
use Carbon\Carbon;
use Tests\TestCase;

/**
 * Lightweight Analytics invariants that do not require a full order fixture
 * graph. Heavier end-to-end consistency checks (margin vs net sales, segment
 * totals vs total_orders) are documented in
 * resources/docs/developer/22-analytics-bi.md for manual/spot verification.
 */
class AnalyticsConsistencyTest extends TestCase
{
    public function test_period_comparison_deltas_handle_zero_previous(): void
    {
        $service = new PeriodComparisonService();

        $result = $service->compare(
            Carbon::parse('2026-09-01')->startOfDay(),
            Carbon::parse('2026-09-03')->endOfDay(),
            function (Carbon $start, Carbon $end) {
                // Current window (Sep 1-3) returns positive sales; previous
                // equal-length window (Aug 29-31) returns zeros.
                if ($start->format('Y-m-d') === '2026-09-01') {
                    return ['total_sales' => 100.0, 'total_orders' => 4];
                }

                return ['total_sales' => 0.0, 'total_orders' => 0];
            }
        );

        $this->assertSame(100.0, $result['current']['total_sales']);
        $this->assertSame(0.0, $result['previous']['total_sales']);
        $this->assertSame(100.0, $result['deltas']['total_sales']);
        $this->assertSame(100.0, $result['deltas']['total_orders']);
        $this->assertSame('2026-08-29', $result['previous_range']['start']->format('Y-m-d'));
        $this->assertSame('2026-08-31', $result['previous_range']['end']->format('Y-m-d'));
    }

    public function test_widget_whitelist_is_stable(): void
    {
        $keys = \App\Services\Concrete\Admin\Analytics\AnalyticsService::widgetKeys();

        $this->assertContains('sales-overview', $keys);
        $this->assertContains('product-margin', $keys);
        $this->assertContains('customer-segments', $keys);
        $this->assertContains('slow-moving', $keys);
        $this->assertNotContains('arbitrary-widget', $keys);
    }
}
