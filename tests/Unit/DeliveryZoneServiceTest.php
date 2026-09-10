<?php

namespace Tests\Unit;

use App\Models\DeliveryZone;
use App\Services\Concrete\Admin\DeliveryZoneService;
use Tests\TestCase;

/**
 * Exercises the pure distance/zone-matching logic behind Delivery Zones
 * without hitting the DB (see CustomerReceivableAccountResolutionTest for
 * the same in-memory-model pattern used elsewhere in this suite).
 */
class DeliveryZoneServiceTest extends TestCase
{
    protected function zone(float $min_km, float $max_km, float $fee): DeliveryZone
    {
        $zone = new DeliveryZone();
        $zone->min_km = $min_km;
        $zone->max_km = $max_km;
        $zone->fee = $fee;

        return $zone;
    }

    public function test_haversine_matches_a_known_real_world_distance(): void
    {
        $service = new DeliveryZoneService();

        // Lahore (31.5497, 74.3436) to Karachi (24.8607, 67.0011) - known
        // great-circle (straight-line, not driving) distance ~1035km.
        $distance = $service->haversineKm(31.5497, 74.3436, 24.8607, 67.0011);

        $this->assertEqualsWithDelta(1035, $distance, 20);
    }

    public function test_matches_a_zone_containing_the_distance(): void
    {
        $service = new DeliveryZoneService();
        $zones = [$this->zone(0, 5, 200), $this->zone(5.1, 8, 350)];

        $zone = $service->matchDistanceToZones(4.0, $zones);

        $this->assertNotNull($zone);
        $this->assertSame(200.0, (float) $zone->fee);
    }

    public function test_matches_the_second_band_when_beyond_the_first(): void
    {
        $service = new DeliveryZoneService();
        $zones = [$this->zone(0, 5, 200), $this->zone(5.1, 8, 350)];

        $zone = $service->matchDistanceToZones(6.0, $zones);

        $this->assertNotNull($zone);
        $this->assertSame(350.0, (float) $zone->fee);
    }

    public function test_returns_null_when_beyond_every_zone(): void
    {
        $service = new DeliveryZoneService();
        $zones = [$this->zone(0, 5, 200), $this->zone(5.1, 8, 350)];

        $this->assertNull($service->matchDistanceToZones(10.0, $zones));
    }

    public function test_returns_null_for_a_gap_before_the_first_zone(): void
    {
        $service = new DeliveryZoneService();
        $zones = [$this->zone(2, 5, 200)];

        $this->assertNull($service->matchDistanceToZones(1.0, $zones));
    }

    public function test_free_delivery_when_cart_total_clears_the_threshold(): void
    {
        $service = new DeliveryZoneService();

        $this->assertTrue($service->isFreeDelivery(2000.0, 2500.0));
        $this->assertTrue($service->isFreeDelivery(2000.0, 2000.0));
        $this->assertFalse($service->isFreeDelivery(2000.0, 1999.99));
    }

    public function test_free_delivery_disabled_when_threshold_is_null(): void
    {
        $service = new DeliveryZoneService();

        $this->assertFalse($service->isFreeDelivery(null, 999999.0));
    }
}
