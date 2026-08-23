<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Models\ServicePurchaseDetail;
use App\Models\ServicePurchaseReturnDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Single reusable Service Purchase report covering Purchase, Purchase
 * Return and combined ("All") views via the `transaction_type` filter.
 * Mirrors ServiceSaleReportService's line-level, single-row-shape grouping
 * design (see that class for the rationale).
 */
class ServicePurchaseReportService
{
    public const TRANSACTION_TYPE_OPTIONS = [
        ''                => 'All',
        'purchase'        => 'Purchase',
        'purchase_return' => 'Purchase Return',
    ];

    public const GROUP_BY_OPTIONS = [
        'none'     => 'By Transaction',
        'supplier' => 'By Supplier',
        'service'  => 'By Service / Item',
    ];

    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::BRANCHADMIN,
        RoleNames::PURCHASEMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    protected function filters(array $obj): array
    {
        return [
            'business_id' => $obj['business_id'] ?? Auth::user()->business_id,
            'branch_id'   => $obj['branch_id'] ?? null,
            'supplier_id' => $obj['supplier_id'] ?? null,
            'status'      => $obj['status'] ?? null,
            'start_date'  => $obj['start_date'] ?? null,
            'end_date'    => $obj['end_date'] ?? null,
        ];
    }

    protected function purchaseLinesQuery(array $filters)
    {
        $query = ServicePurchaseDetail::query()
            ->join('service_purchases', 'service_purchases.service_purchase_id', '=', 'service_purchase_details.service_purchase_id')
            ->leftJoin('suppliers', 'suppliers.supplier_id', '=', 'service_purchases.supplier_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'service_purchases.branch_id')
            ->where('service_purchases.is_deleted', 0);

        $this->applyHeaderFilters($query, $filters, 'service_purchases', 'service_purchase_date');

        return applyRoleScope($query, $this->allow_roles, 'service_purchases.business_id', 'service_purchases.branch_id')
            ->select([
                DB::raw("'" . JournalSourceTypes::SERVICE_PURCHASE . "' as transaction_type"),
                'service_purchases.service_purchase_id as transaction_id',
                'service_purchases.service_purchase_no as transaction_no',
                'service_purchases.service_purchase_date as transaction_date',
                'service_purchases.supplier_id',
                'suppliers.name as supplier_name',
                'service_purchases.branch_id',
                'branches.name as branch_name',
                'service_purchases.status',
                'service_purchase_details.product_id',
                'service_purchase_details.item_name',
                'service_purchase_details.total as line_total',
            ]);
    }

    protected function purchaseReturnLinesQuery(array $filters)
    {
        $query = ServicePurchaseReturnDetail::query()
            ->join('service_purchase_returns', 'service_purchase_returns.service_purchase_return_id', '=', 'service_purchase_return_details.service_purchase_return_id')
            ->leftJoin('suppliers', 'suppliers.supplier_id', '=', 'service_purchase_returns.supplier_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'service_purchase_returns.branch_id')
            ->where('service_purchase_returns.is_deleted', 0);

        $this->applyHeaderFilters($query, $filters, 'service_purchase_returns', 'service_purchase_return_date');

        return applyRoleScope($query, $this->allow_roles, 'service_purchase_returns.business_id', 'service_purchase_returns.branch_id')
            ->select([
                DB::raw("'" . JournalSourceTypes::SERVICE_PURCHASE_RETURN . "' as transaction_type"),
                'service_purchase_returns.service_purchase_return_id as transaction_id',
                'service_purchase_returns.service_purchase_return_no as transaction_no',
                'service_purchase_returns.service_purchase_return_date as transaction_date',
                'service_purchase_returns.supplier_id',
                'suppliers.name as supplier_name',
                'service_purchase_returns.branch_id',
                'branches.name as branch_name',
                'service_purchase_returns.status',
                'service_purchase_return_details.product_id',
                'service_purchase_return_details.item_name',
                'service_purchase_return_details.total as line_total',
            ]);
    }

    protected function applyHeaderFilters($query, array $filters, string $table, string $dateColumn): void
    {
        if (!empty($filters['business_id'])) {
            $query->where("$table.business_id", $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where("$table.branch_id", $filters['branch_id']);
        }
        if (!empty($filters['supplier_id'])) {
            $query->where("$table.supplier_id", $filters['supplier_id']);
        }
        if (!empty($filters['status'])) {
            $query->where("$table.status", $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->where("$table.$dateColumn", '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where("$table.$dateColumn", '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }
    }

    /**
     * Line-level rows (Purchase and/or Purchase Return, per the
     * transaction_type filter) grouped by Transaction, Supplier, or
     * Service/Item, with Purchase/Return/Net amount sums. Shared by
     * getData(), print, PDF and export so every output stays in sync.
     */
    public function build(array $obj): Collection
    {
        $filters = $this->filters($obj);
        $type = $obj['transaction_type'] ?? '';
        $group_by = $obj['group_by'] ?? 'none';

        $lines = collect();
        if ($type !== 'purchase_return') {
            $lines = $lines->merge($this->purchaseLinesQuery($filters)->get());
        }
        if ($type !== 'purchase') {
            $lines = $lines->merge($this->purchaseReturnLinesQuery($filters)->get());
        }

        $groupKey = function ($row) use ($group_by) {
            return match ($group_by) {
                'supplier' => $row->supplier_id ?? 'none',
                'service'  => $row->product_id ?? ($row->item_name ?: 'Uncategorized'),
                default    => $row->transaction_type . '-' . $row->transaction_id,
            };
        };

        $groupLabel = function ($row) use ($group_by) {
            return match ($group_by) {
                'supplier' => $row->supplier_name ?? 'N/A',
                'service'  => $row->item_name ?: 'Uncategorized',
                default    => $row->transaction_no . ' (' . $row->transaction_type . ')',
            };
        };

        return $lines->groupBy($groupKey)->map(function ($groupRows) use ($groupLabel) {
            $first = $groupRows->first();
            $purchaseAmount = (float) $groupRows->where('transaction_type', JournalSourceTypes::SERVICE_PURCHASE)->sum('line_total');
            $purchaseReturnAmount = (float) $groupRows->where('transaction_type', JournalSourceTypes::SERVICE_PURCHASE_RETURN)->sum('line_total');

            return (object) [
                'group_label'            => $groupLabel($first),
                'transaction_count'      => $groupRows->pluck('transaction_id')->unique()->count(),
                'purchase_amount'        => round($purchaseAmount, 2),
                'purchase_return_amount' => round($purchaseReturnAmount, 2),
                'net_amount'             => round($purchaseAmount - $purchaseReturnAmount, 2),
            ];
        })->sortByDesc('net_amount')->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_purchase'        => currency(round($rows->sum('purchase_amount'), 2)),
            'grand_purchase_return' => currency(round($rows->sum('purchase_return_amount'), 2)),
            'grand_net'             => currency(round($rows->sum('net_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('group_label', fn ($row) => $row->group_label)
            ->addColumn('transaction_count', fn ($row) => $row->transaction_count)
            ->addColumn('purchase_amount', fn ($row) => currency($row->purchase_amount))
            ->addColumn('purchase_return_amount', fn ($row) => currency($row->purchase_return_amount))
            ->addColumn('net_amount', fn ($row) => currency($row->net_amount))
            ->with($totals)
            ->make(true);
    }
}
