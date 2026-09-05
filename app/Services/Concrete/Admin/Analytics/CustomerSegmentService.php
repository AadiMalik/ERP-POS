<?php

namespace App\Services\Concrete\Admin\Analytics;

use App\Enums\RoleNames;
use App\Models\Order;
use Carbon\Carbon;

/**
 * New vs Returning customer classification - the second genuinely new
 * metric (no existing report classifies customers this way). A customer's
 * very first ever posted order (across all history, never scoped to the
 * filtered date range) determines whether an order falling inside the
 * filtered range belongs to a "new" or "returning" customer. Orders with no
 * user_id (walk-in / unidentified POS sales) are bucketed separately and
 * never folded into "returning" - they simply aren't classifiable.
 */
class CustomerSegmentService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::BRANCHADMIN,
        RoleNames::GENERALMANAGER,
        RoleNames::OPERATIONMANAGER,
        RoleNames::SALEMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    public function build(array $obj): array
    {
        $business_id = $obj['business_id'] ?? null;
        $start = Carbon::parse($obj['start_date'])->format('Y-m-d');
        $end = Carbon::parse($obj['end_date'])->format('Y-m-d');

        $first_order = Order::query()
            ->where('is_deleted', 0)
            ->where('status', 'posted')
            ->whereNotNull('user_id')
            ->when(!empty($business_id), fn ($q) => $q->where('business_id', $business_id))
            ->selectRaw('user_id, MIN(sale_date) as first_order_date')
            ->groupBy('user_id');

        $in_range = Order::query()
            ->where('is_deleted', 0)
            ->where('status', 'posted')
            ->where('sale_date', '>=', Carbon::parse($obj['start_date'])->startOfDay())
            ->where('sale_date', '<=', Carbon::parse($obj['end_date'])->endOfDay());

        if (!empty($business_id)) {
            $in_range->where('orders.business_id', $business_id);
        }
        if (!empty($obj['branch_id'])) {
            $in_range->where('orders.branch_id', $obj['branch_id']);
        }
        if (!empty($obj['user_id'])) {
            $in_range->where('orders.user_id', $obj['user_id']);
        }

        applyRoleScope($in_range, $this->allow_roles, 'orders.business_id', 'orders.branch_id');

        $rows = $in_range
            ->leftJoinSub($first_order, 'first_order', function ($join) {
                $join->on('first_order.user_id', '=', 'orders.user_id');
            })
            ->selectRaw("
                CASE
                    WHEN orders.user_id IS NULL THEN 'walkin'
                    WHEN DATE(first_order.first_order_date) BETWEEN ? AND ? THEN 'new'
                    ELSE 'returning'
                END as segment
            ", [$start, $end])
            ->selectRaw('COUNT(*) as order_count, SUM(orders.total) as revenue')
            ->groupBy('segment')
            ->get()
            ->keyBy('segment');

        $segment = fn (string $key) => [
            'order_count' => (int) ($rows[$key]->order_count ?? 0),
            'revenue' => round((float) ($rows[$key]->revenue ?? 0), 2),
        ];

        return [
            'new' => $segment('new'),
            'returning' => $segment('returning'),
            'walkin' => $segment('walkin'),
        ];
    }
}
