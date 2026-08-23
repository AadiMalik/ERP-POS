<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Models\ServicePurchase;
use App\Models\ServicePurchaseReturn;
use App\Models\ServiceSale;
use App\Models\ServiceSaleReturn;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Cross-cutting summary of the whole Service Management module - Service
 * Sale, Service Sale Return, Service Purchase and Service Purchase Return
 * headers combined into one grouped view (None/Date/Branch), with Net Sale
 * and Net Purchase computed per group. Complements the two directional
 * reports (ServiceSaleReportService, ServicePurchaseReportService) which
 * drill into customer/supplier/service-wise detail.
 */
class ServiceTransactionSummaryReportService
{
    public const GROUP_BY_OPTIONS = [
        'none'   => 'Overall Total',
        'date'   => 'By Date',
        'branch' => 'By Branch',
    ];

    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::BRANCHADMIN,
        RoleNames::SALEMANAGER,
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
            'status'      => $obj['status'] ?? null,
            'start_date'  => $obj['start_date'] ?? null,
            'end_date'    => $obj['end_date'] ?? null,
        ];
    }

    protected function headerQuery(string $modelClass, string $idColumn, string $dateColumn, string $sourceType, array $filters)
    {
        $table = $this->tableFor($modelClass);

        $query = $modelClass::query()
            ->leftJoin('branches', 'branches.branch_id', '=', "$table.branch_id")
            ->where("$table.is_deleted", 0);

        if (!empty($filters['business_id'])) {
            $query->where("$table.business_id", $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where("$table.branch_id", $filters['branch_id']);
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

        return applyRoleScope($query, $this->allow_roles, "$table.business_id", "$table.branch_id")
            ->select([
                DB::raw("'" . $sourceType . "' as transaction_type"),
                "$table.$idColumn as transaction_id",
                "$table.$dateColumn as transaction_date",
                "$table.branch_id",
                'branches.name as branch_name',
                "$table.total",
            ]);
    }

    protected function tableFor(string $modelClass): string
    {
        return (new $modelClass())->getTable();
    }

    /**
     * Header rows across all four Service Management transaction tables,
     * grouped by the requested dimension (Overall Total / Date / Branch)
     * with Sale, Sale Return, Net Sale, Purchase, Purchase Return and Net
     * Purchase sums per group. Shared by getData(), print, PDF and export.
     */
    public function build(array $obj): Collection
    {
        $filters = $this->filters($obj);
        $group_by = $obj['group_by'] ?? 'none';

        $rows = collect()
            ->merge($this->headerQuery(ServiceSale::class, 'service_sale_id', 'service_sale_date', JournalSourceTypes::SERVICE_SALE, $filters)->get())
            ->merge($this->headerQuery(ServiceSaleReturn::class, 'service_sale_return_id', 'service_sale_return_date', JournalSourceTypes::SERVICE_SALE_RETURN, $filters)->get())
            ->merge($this->headerQuery(ServicePurchase::class, 'service_purchase_id', 'service_purchase_date', JournalSourceTypes::SERVICE_PURCHASE, $filters)->get())
            ->merge($this->headerQuery(ServicePurchaseReturn::class, 'service_purchase_return_id', 'service_purchase_return_date', JournalSourceTypes::SERVICE_PURCHASE_RETURN, $filters)->get());

        $groupKey = function ($row) use ($group_by) {
            return match ($group_by) {
                'date'   => Carbon::parse($row->transaction_date)->format('Y-m-d'),
                'branch' => $row->branch_id ?? 'none',
                default  => 'all',
            };
        };

        $groupLabel = function ($row) use ($group_by) {
            return match ($group_by) {
                'date'   => localDate($row->transaction_date),
                'branch' => $row->branch_name ?? 'Unassigned',
                default  => 'All Transactions',
            };
        };

        return $rows->groupBy($groupKey)->map(function ($groupRows) use ($groupLabel) {
            $first = $groupRows->first();
            $sale = (float) $groupRows->where('transaction_type', JournalSourceTypes::SERVICE_SALE)->sum('total');
            $saleReturn = (float) $groupRows->where('transaction_type', JournalSourceTypes::SERVICE_SALE_RETURN)->sum('total');
            $purchase = (float) $groupRows->where('transaction_type', JournalSourceTypes::SERVICE_PURCHASE)->sum('total');
            $purchaseReturn = (float) $groupRows->where('transaction_type', JournalSourceTypes::SERVICE_PURCHASE_RETURN)->sum('total');

            return (object) [
                'group_label'            => $groupLabel($first),
                'sale_amount'            => round($sale, 2),
                'sale_return_amount'     => round($saleReturn, 2),
                'net_sale_amount'        => round($sale - $saleReturn, 2),
                'purchase_amount'        => round($purchase, 2),
                'purchase_return_amount' => round($purchaseReturn, 2),
                'net_purchase_amount'    => round($purchase - $purchaseReturn, 2),
            ];
        })->sortBy('group_label')->values();
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        $totals = [
            'grand_sale'             => currency(round($rows->sum('sale_amount'), 2)),
            'grand_sale_return'      => currency(round($rows->sum('sale_return_amount'), 2)),
            'grand_net_sale'         => currency(round($rows->sum('net_sale_amount'), 2)),
            'grand_purchase'         => currency(round($rows->sum('purchase_amount'), 2)),
            'grand_purchase_return'  => currency(round($rows->sum('purchase_return_amount'), 2)),
            'grand_net_purchase'     => currency(round($rows->sum('net_purchase_amount'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('group_label', fn ($row) => $row->group_label)
            ->addColumn('sale_amount', fn ($row) => currency($row->sale_amount))
            ->addColumn('sale_return_amount', fn ($row) => currency($row->sale_return_amount))
            ->addColumn('net_sale_amount', fn ($row) => currency($row->net_sale_amount))
            ->addColumn('purchase_amount', fn ($row) => currency($row->purchase_amount))
            ->addColumn('purchase_return_amount', fn ($row) => currency($row->purchase_return_amount))
            ->addColumn('net_purchase_amount', fn ($row) => currency($row->net_purchase_amount))
            ->with($totals)
            ->make(true);
    }
}
