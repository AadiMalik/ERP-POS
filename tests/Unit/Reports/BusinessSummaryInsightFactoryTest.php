<?php

namespace Tests\Unit\Reports;

use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryInsightFactory as Insight;
use App\Support\Permissions\PermissionRegistry;
use App\Support\Permissions\RoleDefaultPermissions;
use App\Enums\RoleNames;
use Carbon\Carbon;
use Tests\TestCase;

class BusinessSummaryInsightFactoryTest extends TestCase
{
    public function test_percent_change_handles_zero_previous(): void
    {
        $this->assertSame(100.0, Insight::percentChange(50, 0));
        $this->assertSame(0.0, Insight::percentChange(0, 0));
        $this->assertSame(-100.0, Insight::percentChange(-10, 0));
    }

    public function test_percent_change_uses_previous_as_base(): void
    {
        $this->assertSame(5.0, Insight::percentChange(105, 100));
        $this->assertSame(-5.0, Insight::percentChange(95, 100));
        $this->assertSame(100.0, Insight::percentChange(20, 10));
    }

    public function test_format_metric_types(): void
    {
        $this->assertSame('12.5%', Insight::formatMetric(12.5, 'percent'));
        $this->assertSame('10%', Insight::formatMetric(10.0, 'percent'));
        $this->assertSame('7', Insight::formatMetric(7, 'count'));
    }

    public function test_previous_period_window_matches_equal_length_range(): void
    {
        $start = Carbon::parse('2026-09-01')->startOfDay();
        $end = Carbon::parse('2026-09-03')->endOfDay();
        $days = $start->diffInDays($end) + 1;
        $previousEnd = $start->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1);

        $this->assertSame(3, $days);
        $this->assertSame('2026-08-29', $previousStart->toDateString());
        $this->assertSame('2026-08-31', $previousEnd->toDateString());
    }

    public function test_permissions_are_registered_for_business_roles(): void
    {
        $all = PermissionRegistry::allNames();
        foreach ([
            'reports.business-summary.view',
            'reports.business-summary.print',
            'reports.business-summary.pdf',
            'reports.business-summary.export',
            'reports.business-summary.export-csv',
        ] as $name) {
            $this->assertContains($name, $all);
            $this->assertContains($name, PermissionRegistry::businessNames());
        }

        $finance = RoleDefaultPermissions::defaultsForRole(RoleNames::FINANCEMANAGER);
        $this->assertContains('reports.business-summary.view', $finance);

        $accountant = RoleDefaultPermissions::defaultsForRole(RoleNames::ACCOUNTANT);
        $this->assertContains('reports.business-summary.view', $accountant);
    }
}
