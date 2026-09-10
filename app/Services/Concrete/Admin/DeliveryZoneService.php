<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Branch;
use App\Models\DeliveryZone;
use App\Repository\Repository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class DeliveryZoneService
{
    protected $model_delivery_zone;

    public function __construct()
    {
        $this->model_delivery_zone = new Repository(new DeliveryZone());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        $wh[] = ['is_deleted', 0];

        $with = ['business', 'branch'];
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];
        $datatable = $this->model_delivery_zone->getModel()::where($wh)
            ->with($with)
            ->orderBy('min_km', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                return '
                    <div class="form-check form-switch mb-0">
                        <input
                            class="form-check-input statusDeliveryZone"
                            type="checkbox"
                            data-id="' . $item->delivery_zone_id . '"
                            ' . $checked . '>
                    </div>
                ';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch?->name ?? '-';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('delivery-zone.edit', $item->delivery_zone_id) . "'
                    id='editDeliveryZone'>
                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteDeliveryZone'
                    data-id='{$item->delivery_zone_id}'>
                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['status', 'branch', 'action'])
            ->make(true);
    }

    /**
     * A zone's [min_km, max_km] band must not overlap another active zone
     * on the same branch - otherwise resolve() would have an ambiguous
     * match. Checked on every save, business_id-scoped like every other
     * uniqueness rule in this module.
     */
    protected function assertNoOverlap(array $obj): void
    {
        $query = DeliveryZone::where('business_id', $obj['business_id'])
            ->where('branch_id', $obj['branch_id'])
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->where('min_km', '<', $obj['max_km'])
            ->where('max_km', '>', $obj['min_km']);

        if (!empty($obj['delivery_zone_id'])) {
            $query->where('delivery_zone_id', '!=', $obj['delivery_zone_id']);
        }

        if ($query->exists()) {
            throw new Exception('This distance range overlaps with another delivery zone for the selected branch.');
        }
    }

    public function save($obj)
    {
        if ((float) $obj['min_km'] >= (float) $obj['max_km']) {
            throw new Exception('Max Distance must be greater than Min Distance.');
        }

        $this->assertNoOverlap($obj);

        if (!empty($obj['delivery_zone_id'])) {
            $obj['updatedby_id'] = Auth::user()->id;
            $obj['date_updated'] = now();
            $this->model_delivery_zone->update($obj, $obj['delivery_zone_id']);
            return $this->model_delivery_zone->find($obj['delivery_zone_id']);
        }

        $obj['delivery_zone_id'] = generateUuid();
        $obj['createdby_id'] = Auth::user()->id;
        $obj['date_created'] = now();
        return $this->model_delivery_zone->create($obj);
    }

    public function getById($delivery_zone_id)
    {
        return $this->model_delivery_zone->find($delivery_zone_id);
    }

    public function status($delivery_zone_id)
    {
        return $this->model_delivery_zone->update([
            'status' => ($this->model_delivery_zone->find($delivery_zone_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $delivery_zone_id);
    }

    public function delete($delivery_zone_id)
    {
        return $this->model_delivery_zone->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $delivery_zone_id);
    }

    public function getByBranch($branch_id)
    {
        return $this->model_delivery_zone->getModel()::where('branch_id', $branch_id)
            ->where('is_deleted', 0)
            ->orderBy('min_km')
            ->get();
    }

    /**
     * Great-circle distance in KM between two lat/long points (spherical
     * Earth approximation - accurate to within ~0.5% for delivery-radius
     * distances, no external API needed).
     */
    public function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth_radius_km = 6371.0;
        $d_lat = deg2rad($lat2 - $lat1);
        $d_lng = deg2rad($lng2 - $lng1);
        $a = sin($d_lat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($d_lng / 2) ** 2;

        return $earth_radius_km * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * True when a cart's total clears the branch's free-delivery threshold.
     * Pure predicate (no DB) so the free-delivery rule is unit-testable
     * without needing a saved Branch row.
     */
    public function isFreeDelivery(?float $threshold, float $cart_total): bool
    {
        return $threshold !== null && $cart_total >= $threshold;
    }

    /**
     * First active zone (in the given, already-ordered list) whose
     * [min_km, max_km] band contains the distance, or null if the point
     * falls outside every band. Pure predicate (no DB) so zone-matching is
     * unit-testable against plain, unsaved DeliveryZone instances.
     */
    public function matchDistanceToZones(float $distance_km, iterable $zones): ?DeliveryZone
    {
        foreach ($zones as $zone) {
            if ($distance_km >= (float) $zone->min_km && $distance_km <= (float) $zone->max_km) {
                return $zone;
            }
        }

        return null;
    }

    /**
     * Resolves the delivery fee for a customer's coordinates against the
     * given branch: free-delivery threshold first (cheapest check, no
     * distance math needed), then the branch's active DeliveryZone bands.
     * Returns in_area=false when the branch has no lat/long configured or
     * the point falls outside every band - callers (WebsiteCheckoutService)
     * treat both the same way: reject as out of delivery area.
     */
    public function resolve(string $business_id, string $branch_id, float $lat, float $lng, float $cart_total = 0): array
    {
        $branch = Branch::where('business_id', $business_id)
            ->where('branch_id', $branch_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$branch) {
            throw new Exception('Branch not found.');
        }

        if ($this->isFreeDelivery(
            $branch->free_delivery_min_order_amount !== null ? (float) $branch->free_delivery_min_order_amount : null,
            $cart_total
        )) {
            return ['in_area' => true, 'fee' => 0.0, 'zone_id' => null, 'free' => true, 'distance_km' => null];
        }

        if (empty($branch->latitude) || empty($branch->longitude)) {
            return ['in_area' => false, 'fee' => 0.0, 'zone_id' => null, 'free' => false, 'distance_km' => null];
        }

        $distance_km = $this->haversineKm((float) $branch->latitude, (float) $branch->longitude, $lat, $lng);

        $zones = DeliveryZone::where('business_id', $business_id)
            ->where('branch_id', $branch_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->orderBy('sort_order')
            ->get();

        $zone = $this->matchDistanceToZones($distance_km, $zones);

        if (!$zone) {
            return ['in_area' => false, 'fee' => 0.0, 'zone_id' => null, 'free' => false, 'distance_km' => round($distance_km, 3)];
        }

        return [
            'in_area' => true,
            'fee' => (float) $zone->fee,
            'zone_id' => $zone->delivery_zone_id,
            'free' => false,
            'distance_km' => round($distance_km, 3),
        ];
    }
}
