<?php

namespace Tests\Unit\Helpers;

use App\Support\DataTables\DataTableFilterEngine;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class ProbeOrder extends Model
{
    public $timestamps = false;
    protected $table = 'probe_orders';
    protected $guarded = [];
}

/**
 * Exercises DataTableFilterEngine's 'date_range' filter branch (via
 * applyByType(), the exact code path fixed during this audit) against an
 * in-memory sqlite table, proving a business-local "from/to" search converts
 * to the correct UTC comparison bound before hitting the query. Uses a
 * standalone Capsule/sqlite instance rather than the app's real connection,
 * matching this suite's no-DB-touch convention for Unit tests. Calls the
 * protected applyByType() directly via reflection to avoid stubbing the
 * full DataTableDefinition/DataTableAuthorizer machinery (permission checks,
 * etc.) that apply() otherwise requires - those are unrelated to the
 * date_range fix under test.
 */
class DataTableFilterEngineDateRangeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        session(['business_setting' => ['timezone' => 'Asia/Karachi']]);

        $capsule = new Capsule();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->create('probe_orders', function ($table) {
            $table->increments('id');
            $table->timestamp('order_date');
        });

        // 2026-08-13 21:30 Asia/Karachi (+5) == 2026-08-13 16:30 UTC.
        Capsule::table('probe_orders')->insert(['order_date' => '2026-08-13 16:30:00']);
        // Just past business-local midnight into Aug 14 - must be excluded
        // from an Aug 13 business-local day filter (a naive
        // Carbon::parse('2026-08-13')->endOfDay() computes 23:59:59 UTC
        // instead of the business-local day boundary, and would wrongly
        // include this row).
        Capsule::table('probe_orders')->insert(['order_date' => '2026-08-13 19:30:00']);
    }

    public function test_date_range_filter_uses_business_timezone_day_boundaries(): void
    {
        $query = ProbeOrder::query();

        $engine = new DataTableFilterEngine();
        $method = new \ReflectionMethod($engine, 'applyByType');
        $method->setAccessible(true);
        $method->invoke(
            $engine,
            $query,
            ['column' => 'order_date', 'type' => 'date_range'],
            ['start' => '2026-08-13', 'end' => '2026-08-13']
        );

        $results = $query->pluck('order_date')->all();

        $this->assertSame(['2026-08-13 16:30:00'], $results);
    }
}
