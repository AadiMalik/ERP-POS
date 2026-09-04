<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Models\ProductVariationStock;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Stock Aging + Slow/Fast/Non-Moving via report_mode (aging|velocity).
 * Last movement from product_variation_stock_transactions.
 */
class StockAgingReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);
        $mode = $filters['report_mode'] ?? 'aging';
        $age_bucket = $obj['age_bucket'] ?? null;
        $movement_class = $obj['movement_class'] ?? null;
        $now = Carbon::now();

        $lastTxn = DB::table('product_variation_stock_transactions')
            ->select(
                'business_id',
                'warehouse_id',
                'product_variation_id',
                DB::raw('MAX(transaction_date) as last_movement_date')
            )
            ->where('is_deleted', 0)
            ->groupBy('business_id', 'warehouse_id', 'product_variation_id');

        $q = ProductVariationStock::query()
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'product_variation_stocks.warehouse_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'warehouses.branch_id')
            ->join('products', 'products.product_id', '=', 'product_variation_stocks.product_id')
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_stocks.product_variation_id')
            ->leftJoinSub($lastTxn, 'last_txn', function ($join) {
                $join->on('last_txn.business_id', '=', 'product_variation_stocks.business_id')
                    ->on('last_txn.warehouse_id', '=', 'product_variation_stocks.warehouse_id')
                    ->on('last_txn.product_variation_id', '=', 'product_variation_stocks.product_variation_id');
            })
            ->where('product_variation_stocks.is_deleted', 0)
            ->where('product_variation_stocks.quantity', '>', 0);

        if (!empty($filters['business_id'])) {
            $q->where('product_variation_stocks.business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where('warehouses.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('product_variation_stocks.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('product_variation_stocks.product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('product_variation_stocks.product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($filters['category_id'])) {
            $q->where('products.category_id', $filters['category_id']);
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'product_variation_stocks.business_id', 'warehouses.branch_id');

        $rows = $q->get([
            'product_variation_stocks.product_id',
            'product_variation_stocks.product_variation_id',
            'product_variation_stocks.warehouse_id',
            'product_variation_stocks.business_id',
            'product_variation_stocks.quantity',
            'product_variation_stocks.avg_price',
            'product_variation_stocks.date_created',
            'products.name as product_name',
            'product_variations.name as variation_name',
            'warehouses.name as warehouse_name',
            'branches.name as branch_name',
            'last_txn.last_movement_date',
        ])->map(function ($row) use ($now) {
            $last = $row->last_movement_date ?: $row->date_created;
            $days = $last ? Carbon::parse($last)->diffInDays($now) : 9999;
            $class = $days <= 30 ? 'fast' : ($days <= 90 ? 'slow' : 'non_moving');
            $bucket = $days <= 30 ? '0-30' : ($days <= 60 ? '31-60' : ($days <= 90 ? '61-90' : '90+'));
            $qty = (float) $row->quantity;
            $avg = (float) $row->avg_price;

            return (object) [
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'warehouse_name' => $row->warehouse_name,
                'branch_name' => $row->branch_name,
                'quantity' => $qty,
                'avg_price' => $avg,
                'stock_value' => round($qty * $avg, 2),
                'last_movement_date' => $last,
                'days_idle' => $days,
                'age_bucket' => $bucket,
                'movement_class' => $class,
                'movement_class_label' => ucfirst(str_replace('_', ' ', $class)),
                'ledger_url' => url('/admin/reports/stock-ledger') . '?' . http_build_query([
                    'product_id' => $row->product_id,
                    'product_variation_id' => $row->product_variation_id,
                    'warehouse_id' => $row->warehouse_id,
                    'business_id' => $row->business_id,
                ]),
            ];
        });

        if ($mode === 'velocity' && !empty($movement_class)) {
            $rows = $rows->where('movement_class', $movement_class)->values();
        }
        if ($mode === 'aging' && !empty($age_bucket)) {
            $rows = $rows->where('age_bucket', $age_bucket)->values();
        }

        return $rows;
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $totals = [
            'total_qty' => decimal(round($rows->sum('quantity'), 3)),
            'total_value' => currency(round($rows->sum('stock_value'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('product_name', fn ($row) => '<a href="' . e($row->ledger_url) . '">' . e($row->product_name) . '</a>')
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('branch_name', fn ($row) => e($row->branch_name ?? '-'))
            ->addColumn('quantity', fn ($row) => decimal($row->quantity))
            ->addColumn('stock_value', fn ($row) => currency($row->stock_value))
            ->addColumn('last_movement_date', fn ($row) => $row->last_movement_date ? localDate($row->last_movement_date) : '-')
            ->addColumn('days_idle', fn ($row) => $row->days_idle)
            ->addColumn('age_bucket', fn ($row) => $row->age_bucket)
            ->addColumn('movement_class_label', fn ($row) => $row->movement_class_label)
            ->rawColumns(['product_name'])
            ->with($totals)
            ->make(true);
    }
}
