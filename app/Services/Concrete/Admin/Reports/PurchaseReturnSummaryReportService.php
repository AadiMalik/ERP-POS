<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Enums\Status;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class PurchaseReturnSummaryReportService
{
    public const GROUP_BY_OPTIONS = [
        'none'              => 'Summary (By Return)',
        'supplier'          => 'By Supplier',
        'product_variation' => 'By Product / Variation',
        'branch'            => 'By Branch',
        'warehouse'         => 'By Warehouse',
        'date'              => 'By Date',
    ];

    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
        RoleNames::PURCHASEMANAGER,
        RoleNames::INVENTORYMANAGER,
    ];

    public function __construct(protected PurchaseReturnQueryService $query_service)
    {
    }

    protected function filters(array $obj): array
    {
        return [
            'business_id'          => $obj['business_id'] ?? Auth::user()->business_id,
            'branch_id'            => $obj['branch_id'] ?? null,
            'supplier_id'          => $obj['supplier_id'] ?? null,
            'warehouse_id'         => $obj['warehouse_id'] ?? null,
            'product_id'           => $obj['product_id'] ?? null,
            'product_variation_id' => $obj['product_variation_id'] ?? null,
            'return_type'          => $obj['return_type'] ?? null,
            'status'               => $obj['status'] ?? null,
            'start_date'           => $obj['start_date'] ?? null,
            'end_date'             => $obj['end_date'] ?? null,
            'allow_roles'          => $this->allow_roles,
        ];
    }

    /**
     * Line-level rows grouped by the requested dimension (Supplier,
     * Product/Variation, Branch, Warehouse, Date, or ungrouped "By Return"),
     * with quantity/discount/tax/subtotal/total sums and a reconciliation
     * indicator: how many APPROVED returns in the group still have no
     * posted accounting voucher (a genuine anomaly - approval normally
     * auto-posts, see PurchaseReturnService::applyPurchaseReturnPosting()).
     * Pending/cancelled returns are never posted by design and are not
     * counted against this indicator. Shared by getData(), print, PDF and
     * export so every output stays in sync.
     */
    public function build(array $obj): Collection
    {
        $filters = $this->filters($obj);
        $group_by = $obj['group_by'] ?? 'none';

        $lines = $this->query_service->baseQuery($filters)
            ->orderBy('purchase_returns.purchase_return_date')
            ->get([
                'purchase_returns.purchase_return_id',
                'purchase_returns.purchase_return_no',
                'purchase_returns.purchase_return_date',
                'purchase_returns.status',
                'purchase_returns.supplier_id',
                'purchase_returns.branch_id',
                'purchase_returns.warehouse_id',
                'suppliers.name as supplier_name',
                'branches.name as branch_name',
                'warehouses.name as warehouse_name',
                'purchase_return_details.product_variation_id',
                'product_variations.name as variation_name',
                'purchase_return_details.return_quantity',
                'purchase_return_details.subtotal',
                'purchase_return_details.discount_amount',
                'purchase_return_details.tax_amount',
                'purchase_return_details.total',
            ]);

        $postedIds = $this->query_service->postedMap($filters);

        $groupKey = function ($row) use ($group_by) {
            return match ($group_by) {
                'supplier'          => $row->supplier_id,
                'product_variation' => $row->product_variation_id ?? 'none',
                'branch'            => $row->branch_id ?? 'none',
                'warehouse'         => $row->warehouse_id,
                'date'              => Carbon::parse($row->purchase_return_date)->format('Y-m-d'),
                default             => $row->purchase_return_id,
            };
        };

        $groupLabel = function ($row) use ($group_by) {
            return match ($group_by) {
                'supplier'          => $row->supplier_name,
                'product_variation' => $row->variation_name ?? 'N/A',
                'branch'            => $row->branch_name ?? 'Unassigned',
                'warehouse'         => $row->warehouse_name,
                'date'              => localDate($row->purchase_return_date),
                default             => $row->purchase_return_no,
            };
        };

        return $lines->groupBy($groupKey)->map(function ($groupRows) use ($groupLabel, $postedIds) {
            $first = $groupRows->first();
            $returnIds = $groupRows->pluck('purchase_return_id')->unique();
            $approvedIds = $groupRows->where('status', Status::APPROVED)->pluck('purchase_return_id')->unique();

            return (object) [
                'group_label'    => $groupLabel($first),
                'return_count'   => $returnIds->count(),
                'total_qty'      => round((float) $groupRows->sum('return_quantity'), 3),
                'total_subtotal' => round((float) $groupRows->sum('subtotal'), 2),
                'total_discount' => round((float) $groupRows->sum('discount_amount'), 2),
                'total_tax'      => round((float) $groupRows->sum('tax_amount'), 2),
                'total_amount'   => round((float) $groupRows->sum('total'), 2),
                'posted_count'   => $approvedIds->filter(fn ($id) => $postedIds->contains($id))->count(),
                'unposted_count' => $approvedIds->filter(fn ($id) => !$postedIds->contains($id))->count(),
            ];
        })->sortByDesc('total_amount')->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_qty'      => decimal(round($rows->sum('total_qty'), 3)),
            'grand_subtotal' => currency(round($rows->sum('total_subtotal'), 2)),
            'grand_discount' => currency(round($rows->sum('total_discount'), 2)),
            'grand_tax'      => currency(round($rows->sum('total_tax'), 2)),
            'grand_total'    => currency(round($rows->sum('total_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('group_label', fn ($row) => $row->group_label)
            ->addColumn('return_count', fn ($row) => $row->return_count)
            ->addColumn('total_qty', fn ($row) => decimal($row->total_qty))
            ->addColumn('total_subtotal', fn ($row) => currency($row->total_subtotal))
            ->addColumn('total_discount', fn ($row) => currency($row->total_discount))
            ->addColumn('total_tax', fn ($row) => currency($row->total_tax))
            ->addColumn('total_amount', fn ($row) => currency($row->total_amount))
            ->addColumn('reconciliation', function ($row) {
                if ($row->unposted_count > 0) {
                    return $row->posted_count . ' Posted / <span class="text-warning">' . $row->unposted_count .
                        ' Unposted <i class="fa fa-exclamation-triangle" title="Approved but not yet posted to accounting"></i></span>';
                }

                return $row->posted_count . ' Posted';
            })
            ->rawColumns(['reconciliation'])
            ->with($totals)
            ->make(true);
    }
}
