<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Models\ServiceSaleDetail;
use App\Models\ServiceSaleReturnDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Single reusable Service Sale report covering Sale, Sale Return and
 * combined ("All") views via the `transaction_type` filter, instead of
 * separate reports per variation. Always operates on line-level rows
 * (service_sale_details / service_sale_return_details joined to their
 * headers) so every GROUP_BY_OPTIONS dimension - including the ungrouped
 * "By Transaction" view - collapses to the same aggregate row shape
 * (label, count, sale amount, sale return amount, net amount), mirroring
 * PurchaseReturnSummaryReportService's grouping design.
 */
class ServiceSaleReportService
{
    public const TRANSACTION_TYPE_OPTIONS = [
        ''            => 'All',
        'sale'        => 'Sale',
        'sale_return' => 'Sale Return',
    ];

    public const GROUP_BY_OPTIONS = [
        'none'     => 'By Transaction',
        'customer' => 'By Customer',
        'service'  => 'By Service / Item',
    ];

    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::BRANCHADMIN,
        RoleNames::SALEMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    protected function filters(array $obj): array
    {
        return [
            'business_id'      => $obj['business_id'] ?? Auth::user()->business_id,
            'branch_id'        => $obj['branch_id'] ?? null,
            'customer_id'      => $obj['customer_id'] ?? null,
            'status'           => $obj['status'] ?? null,
            'start_date'       => $obj['start_date'] ?? null,
            'end_date'         => $obj['end_date'] ?? null,
        ];
    }

    protected function saleLinesQuery(array $filters)
    {
        $query = ServiceSaleDetail::query()
            ->join('service_sales', 'service_sales.service_sale_id', '=', 'service_sale_details.service_sale_id')
            ->leftJoin('users as service_sale_customers', 'service_sale_customers.id', '=', 'service_sales.customer_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'service_sales.branch_id')
            ->where('service_sales.is_deleted', 0);

        $this->applyHeaderFilters($query, $filters, 'service_sales', 'service_sale_date');

        return applyRoleScope($query, $this->allow_roles, 'service_sales.business_id', 'service_sales.branch_id')
            ->select([
                DB::raw("'" . JournalSourceTypes::SERVICE_SALE . "' as transaction_type"),
                'service_sales.service_sale_id as transaction_id',
                'service_sales.service_sale_no as transaction_no',
                'service_sales.service_sale_date as transaction_date',
                'service_sales.customer_id',
                'service_sale_customers.name as customer_name',
                'service_sales.branch_id',
                'branches.name as branch_name',
                'service_sales.status',
                'service_sale_details.product_id',
                'service_sale_details.item_name',
                'service_sale_details.total as line_total',
            ]);
    }

    protected function saleReturnLinesQuery(array $filters)
    {
        $query = ServiceSaleReturnDetail::query()
            ->join('service_sale_returns', 'service_sale_returns.service_sale_return_id', '=', 'service_sale_return_details.service_sale_return_id')
            ->leftJoin('users as service_sale_return_customers', 'service_sale_return_customers.id', '=', 'service_sale_returns.customer_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'service_sale_returns.branch_id')
            ->where('service_sale_returns.is_deleted', 0);

        $this->applyHeaderFilters($query, $filters, 'service_sale_returns', 'service_sale_return_date');

        return applyRoleScope($query, $this->allow_roles, 'service_sale_returns.business_id', 'service_sale_returns.branch_id')
            ->select([
                DB::raw("'" . JournalSourceTypes::SERVICE_SALE_RETURN . "' as transaction_type"),
                'service_sale_returns.service_sale_return_id as transaction_id',
                'service_sale_returns.service_sale_return_no as transaction_no',
                'service_sale_returns.service_sale_return_date as transaction_date',
                'service_sale_returns.customer_id',
                'service_sale_return_customers.name as customer_name',
                'service_sale_returns.branch_id',
                'branches.name as branch_name',
                'service_sale_returns.status',
                'service_sale_return_details.product_id',
                'service_sale_return_details.item_name',
                'service_sale_return_details.total as line_total',
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
        if (!empty($filters['customer_id'])) {
            $query->where("$table.customer_id", $filters['customer_id']);
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
     * Line-level rows (Sale and/or Sale Return, per the transaction_type
     * filter) grouped by the requested dimension - Transaction (ungrouped
     * detail), Customer, or Service/Item - with Sale/Return/Net amount sums.
     * Shared by getData(), print, PDF and export so every output stays in sync.
     */
    public function build(array $obj): Collection
    {
        $filters = $this->filters($obj);
        $type = $obj['transaction_type'] ?? '';
        $group_by = $obj['group_by'] ?? 'none';

        $lines = collect();
        if ($type !== 'sale_return') {
            $lines = $lines->merge($this->saleLinesQuery($filters)->get());
        }
        if ($type !== 'sale') {
            $lines = $lines->merge($this->saleReturnLinesQuery($filters)->get());
        }

        $groupKey = function ($row) use ($group_by) {
            return match ($group_by) {
                'customer' => $row->customer_id ?? 'none',
                'service'  => $row->product_id ?? ($row->item_name ?: 'Uncategorized'),
                default    => $row->transaction_type . '-' . $row->transaction_id,
            };
        };

        $groupLabel = function ($row) use ($group_by) {
            return match ($group_by) {
                'customer' => $row->customer_name ?? 'Walk-in',
                'service'  => $row->item_name ?: 'Uncategorized',
                default    => $row->transaction_no . ' (' . $row->transaction_type . ')',
            };
        };

        return $lines->groupBy($groupKey)->map(function ($groupRows) use ($groupLabel) {
            $first = $groupRows->first();
            $saleAmount = (float) $groupRows->where('transaction_type', JournalSourceTypes::SERVICE_SALE)->sum('line_total');
            $saleReturnAmount = (float) $groupRows->where('transaction_type', JournalSourceTypes::SERVICE_SALE_RETURN)->sum('line_total');

            return (object) [
                'group_label'        => $groupLabel($first),
                'transaction_count'  => $groupRows->pluck('transaction_id')->unique()->count(),
                'sale_amount'        => round($saleAmount, 2),
                'sale_return_amount' => round($saleReturnAmount, 2),
                'net_amount'         => round($saleAmount - $saleReturnAmount, 2),
            ];
        })->sortByDesc('net_amount')->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_sale'        => currency(round($rows->sum('sale_amount'), 2)),
            'grand_sale_return' => currency(round($rows->sum('sale_return_amount'), 2)),
            'grand_net'         => currency(round($rows->sum('net_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('group_label', fn ($row) => $row->group_label)
            ->addColumn('transaction_count', fn ($row) => $row->transaction_count)
            ->addColumn('sale_amount', fn ($row) => currency($row->sale_amount))
            ->addColumn('sale_return_amount', fn ($row) => currency($row->sale_return_amount))
            ->addColumn('net_amount', fn ($row) => currency($row->net_amount))
            ->with($totals)
            ->make(true);
    }
}
