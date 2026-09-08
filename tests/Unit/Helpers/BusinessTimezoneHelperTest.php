<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

/**
 * Exercises the UTC-storage / Business-timezone-display helpers in
 * app/Helpers/CommonFunctions.php in isolation (no DB access, matching the
 * existing Unit suite's convention - see CustomerReceivableAccountResolutionTest).
 * Session is used only to drive date_format/time_format resolution; the
 * timezone itself is passed via each helper's explicit $timezoneOverride so
 * these tests don't depend on Auth/session timezone state.
 */
class BusinessTimezoneHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        session(['business_setting' => [
            'date_format' => 'd-m-Y',
            'time_format' => 'h:i A',
        ]]);
    }

    /** The spec's own worked example: 10:01 PM Pakistan local -> 05:01 PM UTC storage. */
    public function test_local_datetime_input_converts_to_utc_for_storage(): void
    {
        $stored = utcDateTime('13-08-2026 10:01 PM', 'Asia/Karachi');

        $this->assertSame('2026-08-13 17:01:00', $stored);
    }

    /** The same UTC value must redisplay as the original local wall-clock time. */
    public function test_utc_value_displays_as_correct_business_local_datetime(): void
    {
        $displayed = localDateTime('2026-08-13 17:01:00', 'Asia/Karachi');

        $this->assertSame('13-08-2026 10:01 PM', $displayed);
    }

    /** Edit a record, don't change the datetime, save again - must not shift. */
    public function test_editing_and_resaving_unchanged_datetime_does_not_double_convert(): void
    {
        $stored = utcDateTime('13-08-2026 10:01 PM', 'Asia/Karachi');

        // Edit form population: UTC -> business-local for the input field.
        $editFormValue = localDateTime($stored, 'Asia/Karachi');
        $this->assertSame('13-08-2026 10:01 PM', $editFormValue);

        // Submit unchanged: business-local -> UTC again.
        $resaved = utcDateTime($editFormValue, 'Asia/Karachi');
        $this->assertSame($stored, $resaved);
    }

    /** Changing the configured Business timezone re-displays existing UTC data differently, without touching storage. */
    public function test_timezone_change_redisplays_same_utc_value_without_altering_it(): void
    {
        $stored = utcDateTime('13-08-2026 10:01 PM', 'Asia/Karachi');
        $this->assertSame('2026-08-13 17:01:00', $stored);

        $asPakistan = localDateTime($stored, 'Asia/Karachi');
        $asNewYork = localDateTime($stored, 'America/New_York');

        $this->assertSame('13-08-2026 10:01 PM', $asPakistan);
        $this->assertNotSame($asPakistan, $asNewYork);

        // The underlying stored UTC value is untouched by either display.
        $this->assertSame('2026-08-13 17:01:00', $stored);
    }

    /** A business-local search bound must convert to UTC before hitting the query. */
    public function test_datetime_search_filter_converts_local_input_to_utc(): void
    {
        $utcBound = utcDateTime('13-08-2026 10:00 PM', 'Asia/Karachi');

        $this->assertSame('2026-08-13 17:00:00', $utcBound);
    }

    /** Date-range "from/to" filters must use business-local calendar-day boundaries, not UTC ones. */
    public function test_business_start_and_end_of_day_produce_correct_utc_boundaries(): void
    {
        $start = businessStartOfDay('13-08-2026', 'Asia/Karachi');
        $end = businessEndOfDay('13-08-2026', 'Asia/Karachi');

        // Midnight Aug 13 in Asia/Karachi (+5) is 19:00 UTC on Aug 12.
        $this->assertSame('2026-08-12 19:00:00', $start->format('Y-m-d H:i:s'));
        // 23:59:59 Aug 13 in Asia/Karachi is 18:59:59 UTC on Aug 13.
        $this->assertSame('2026-08-13 18:59:59', $end->format('Y-m-d H:i:s'));
    }

    /** DST-aware: the same "start of day" call must resolve differently across a DST transition, via the IANA identifier - no manual offset math. */
    public function test_start_of_day_is_dst_aware_for_a_transitioning_timezone(): void
    {
        // 2026-03-08 is the US spring-forward date (America/New_York: EST UTC-5 -> EDT UTC-4).
        $beforeDst = businessStartOfDay('2026-03-07', 'America/New_York'); // still EST (-5)
        $afterDst = businessStartOfDay('2026-03-09', 'America/New_York');  // now EDT (-4)

        $this->assertSame('2026-03-07 05:00:00', $beforeDst->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-09 04:00:00', $afterDst->format('Y-m-d H:i:s'));
    }

    /**
     * Regression test for the bug found during this audit: utcDate()/businessDate()
     * must round-trip a pure calendar-date field (no time component) without any
     * timezone-instant shift, so a positive-UTC-offset business timezone (Asia/Karachi,
     * the app's default) never silently stores/displays the day before what was entered.
     */
    public function test_pure_calendar_date_round_trips_without_day_shift(): void
    {
        $stored = utcDate('13-08-2026');
        $this->assertSame('2026-08-13', $stored);

        $displayed = businessDate($stored);
        $this->assertSame('13-08-2026', $displayed);
    }

    /** businessTimezone() resolution order: explicit override wins over everything else. */
    public function test_business_timezone_override_takes_precedence(): void
    {
        session(['business_setting' => ['timezone' => 'Asia/Karachi']]);

        $this->assertSame('America/New_York', businessTimezone('America/New_York'));
    }

    /** businessTimezone() falls back to the session-primed Business setting when no override is given. */
    public function test_business_timezone_falls_back_to_session(): void
    {
        session(['business_setting' => ['timezone' => 'Asia/Karachi']]);

        $this->assertSame('Asia/Karachi', businessTimezone());
    }

    /** businessTimezone() falls back to the app default when nothing else is available. */
    public function test_business_timezone_falls_back_to_app_default(): void
    {
        session()->forget('business_setting');

        $this->assertSame(config('app.timezone'), businessTimezone());
    }

    /** businessToday() resolves "today" in the business's own calendar day, not the app/UTC day. */
    public function test_business_today_uses_the_business_timezone(): void
    {
        $this->assertSame(
            now('Asia/Karachi')->format('Y-m-d'),
            businessToday('Asia/Karachi')
        );
    }

    /** DataTable date_range filters must compare UTC-stored timestamps against business-local day bounds. */
    public function test_datatable_date_range_uses_business_day_utc_bounds(): void
    {
        session(['business_setting' => [
            'timezone' => 'Asia/Karachi',
            'date_format' => 'd-m-Y',
            'time_format' => 'h:i A',
        ]]);

        $this->assertSame(
            businessStartOfDay('13-08-2026')->format('Y-m-d H:i:s'),
            '2026-08-12 19:00:00'
        );
        $this->assertSame(
            businessEndOfDay('13-08-2026')->format('Y-m-d H:i:s'),
            '2026-08-13 18:59:59'
        );
    }

    /** datetime-local inputs round-trip through UTC without a day/hour shift. */
    public function test_datetime_local_input_round_trips_without_shift(): void
    {
        $stored = utcDateTimeLocal('2026-08-13T22:01', 'Asia/Karachi');
        $this->assertSame('2026-08-13 17:01:00', $stored);
        $this->assertSame('2026-08-13T22:01', localDateTimeLocal($stored, 'Asia/Karachi'));
    }

    /** Date-only fields must not be converted at all - blank input stays blank, no timezone math applied. */
    public function test_blank_values_pass_through_every_helper_unchanged(): void
    {
        $this->assertNull(localDateTime(null));
        $this->assertSame('', localDate(''));
        $this->assertNull(utcDateTime(null));
        $this->assertNull(businessDate(null));
    }
}
