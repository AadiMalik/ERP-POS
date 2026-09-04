<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Enums\TransactionType;
use App\Models\StockTakingDetail;
use App\Services\Concrete\Admin\ReferenceResolverService;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use App\Services\Concrete\Admin\Reports\StockLedgerQueryService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Stock Reconciliation & Adjustment — stock takes + adjustment movements.
 * report_mode: stock_take (default) | adjustments
 */
class StockReconciliationReportService
{
    use AppliesInventoryReportScope;

    public function __construct(
        protected StockLedgerQueryService $ledger_query_service,
        protected ReferenceResolverService $reference_resolver
    ) {
    }

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);
        $mode = $filters['report_mode'] ?? 'stock_take';

        if ($mode === 'adjustments') {
            return $this->buildAdjustments($filters, $obj);
        }

        return $this->buildStockTakes($filters);
    }

    protected function buildStockTakes(array $filters): Collection
    {
        $q = StockTakingDetail::query()
            ->join('stock_takings', 'stock_takings.stock_taking_id', '=', 'stock_taking_details.stock_taking_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'stock_takings.warehouse_id')
            ->join('products', 'products.product_id', '=', 'stock_taking_details.product_id')
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'stock_taking_details.product_variation_id')
            ->where('stock_takings.is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $q->where('stock_takings.business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where('stock_takings.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('stock_takings.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('stock_taking_details.product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('stock_taking_details.product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($filters['status'])) {
            $q->where('stock_takings.status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $q->where('stock_takings.stock_taking_date', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $q->where('stock_takings.stock_taking_date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'stock_takings.business_id', 'stock_takings.branch_id');

        return $q->orderByDesc('stock_takings.stock_taking_date')->get([
            'stock_takings.stock_taking_id',
            'stock_takings.stock_taking_no',
            'stock_takings.stock_taking_date',
            'stock_takings.status',
            'stock_takings.warehouse_id',
            'stock_takings.business_id',
            'warehouses.name as warehouse_name',
            'products.name as product_name',
            'product_variations.name as variation_name',
            'stock_taking_details.product_id',
            'stock_taking_details.product_variation_id',
            'stock_taking_details.system_quantity',
            'stock_taking_details.physical_quantity',
            'stock_taking_details.difference_quantity',
            'stock_taking_details.unit_cost',
            'stock_taking_details.difference_value',
        ])->map(function ($row) {
            return (object) [
                'doc_no' => $row->stock_taking_no,
                'doc_date' => $row->stock_taking_date,
                'warehouse_name' => $row->warehouse_name,
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'system_quantity' => (float) $row->system_quantity,
                'physical_quantity' => (float) $row->physical_quantity,
                'difference_quantity' => (float) $row->difference_quantity,
                'unit_cost' => (float) $row->unit_cost,
                'difference_value' => (float) $row->difference_value,
                'status' => $row->status,
                'movement_type' => 'Stock Taking',
                'edit_url' => url('/admin/stock-taking/' . $row->stock_taking_id . '/edit'),
                'ledger_url' => url('/admin/reports/stock-ledger') . '?' . http_build_query([
                    'product_id' => $row->product_id,
                    'product_variation_id' => $row->product_variation_id,
                    'warehouse_id' => $row->warehouse_id,
                    'business_id' => $row->business_id,
                    'reference_type' => 'stock_taking',
                ]),
            ];
        });
    }

    protected function buildAdjustments(array $filters, array $obj): Collection
    {
        $ledgerFilters = array_merge($filters, [
            'transaction_types' => [
                TransactionType::ADJUSTMENT,
                TransactionType::STOCK_TAKE_INCREASE,
                TransactionType::STOCK_TAKE_DECREASE,
            ],
        ]);
        unset($ledgerFilters['transaction_type']);

        $types = TransactionType::getOptions();

        return $this->ledger_query_service->transactions($ledgerFilters)->map(function ($row) use ($types) {
            $is_in = TransactionType::isInbound($row->transaction_type);
            $qty = (float) $row->base_quantity;

            return (object) [
                'doc_no' => $this->reference_resolver->resolveDocNo($row->reference_type, $row->reference_id),
                'doc_date' => $row->transaction_date,
                'warehouse_name' => $row->warehouse_name,
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'system_quantity' => null,
                'physical_quantity' => null,
                'difference_quantity' => $is_in ? $qty : -$qty,
                'unit_cost' => (float) $row->unit_price,
                'difference_value' => round($qty * (float) $row->unit_price, 2) * ($is_in ? 1 : -1),
                'status' => '-',
                'movement_type' => $types[$row->transaction_type] ?? $row->transaction_type,
                'edit_url' => $this->reference_resolver->resolveUrl($row->reference_type, $row->reference_id) ?? '#',
                'ledger_url' => url('/admin/reports/stock-ledger') . '?' . http_build_query([
                    'product_id' => $row->product_id,
                    'product_variation_id' => $row->product_variation_id,
                    'warehouse_id' => $row->warehouse_id,
                    'business_id' => $row->business_id,
                    'transaction_type' => $row->transaction_type,
                ]),
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $totals = [
            'total_diff_qty' => decimal(round($rows->sum('difference_quantity'), 3)),
            'total_diff_value' => currency(round($rows->sum('difference_value'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('doc_no', fn ($row) => $row->edit_url && $row->edit_url !== '#'
                ? '<a href="' . e($row->edit_url) . '">' . e($row->doc_no) . '</a>'
                : e($row->doc_no))
            ->addColumn('doc_date', fn ($row) => localDate($row->doc_date))
            ->addColumn('movement_type', fn ($row) => e($row->movement_type))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('product_name', fn ($row) => '<a href="' . e($row->ledger_url) . '">' . e($row->product_name) . '</a>')
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('system_quantity', fn ($row) => $row->system_quantity !== null ? decimal($row->system_quantity) : '-')
            ->addColumn('physical_quantity', fn ($row) => $row->physical_quantity !== null ? decimal($row->physical_quantity) : '-')
            ->addColumn('difference_quantity', fn ($row) => decimal($row->difference_quantity))
            ->addColumn('unit_cost', fn ($row) => currency($row->unit_cost))
            ->addColumn('difference_value', fn ($row) => currency($row->difference_value))
            ->addColumn('status', fn ($row) => e(ucfirst((string) $row->status)))
            ->rawColumns(['doc_no', 'product_name'])
            ->with($totals)
            ->make(true);
    }
}
