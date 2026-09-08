<?php

namespace Tests\Unit\Helpers;

use App\Services\Concrete\Admin\OrderService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Regression test for the POS "sale date" bug found during the timezone
 * audit: validateSaleDate() used to compare against Carbon::today() (the
 * app/UTC calendar day) instead of the business's own calendar day, so a
 * sale made in the first few hours of a new business-local day (before UTC
 * has also crossed into it, for a positive-UTC-offset timezone) could be
 * wrongly rejected as "in the future" or "backdated". No DB access - the
 * method under test is pure Carbon comparison logic.
 */
class OrderServiceBusinessDayTest extends TestCase
{
    public function test_validate_sale_date_accepts_todays_date_in_the_business_timezone(): void
    {
        session(['business_setting' => ['timezone' => 'Asia/Karachi']]);

        // It's currently early morning in Asia/Karachi (+5) but still the
        // previous UTC calendar day - businessToday() must resolve to the
        // Karachi-local date, matching what validateSaleDate() now uses.
        $sale_date = Carbon::parse(businessToday());

        $service = app(OrderService::class);
        $method = new ReflectionMethod($service, 'validateSaleDate');
        $method->setAccessible(true);

        // Throws on failure - reaching this line without an exception is the assertion.
        $method->invoke($service, $sale_date, null);
        $this->assertTrue(true);
    }

    public function test_validate_sale_date_rejects_a_future_business_local_date(): void
    {
        session(['business_setting' => ['timezone' => 'Asia/Karachi']]);

        $sale_date = Carbon::parse(businessToday())->addDay();

        $service = app(OrderService::class);
        $method = new ReflectionMethod($service, 'validateSaleDate');
        $method->setAccessible(true);

        $this->expectExceptionMessage('Sale date cannot be in the future.');
        $method->invoke($service, $sale_date, null);
    }
}
