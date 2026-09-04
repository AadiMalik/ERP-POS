<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Models\ProductVariationBatch;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Batch/Lot Stock + Expiry & Near Expiry via report_mode (batch_stock|expiry).
 */
class BatchExpiryReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        $filters = $this->baseFilters($obj);
        $mode = $filters['report_mode'] ?? 'batch_stock';
        $expiry_within_days = isset($obj['expiry_within_days']) && $obj['expiry_within_days'] !== ''
            ? (int) $obj['expiry_within_days']
            : null;
        $expired_only = !empty($obj['expired_only']);
        $now = Carbon::now()->startOfDay();

        $q = ProductVariationBatch::query()
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'product_variation_batches.warehouse_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'warehouses.branch_id')
            ->join('products', 'products.product_id', '=', 'product_variation_batches.product_id')
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_batches.product_variation_id')
            ->where('product_variation_batches.is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $q->where('product_variation_batches.business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $q->where('warehouses.branch_id', $filters['branch_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('product_variation_batches.warehouse_id', $filters['warehouse_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('product_variation_batches.product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('product_variation_batches.product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($obj['batch_no'])) {
            $q->where('product_variation_batches.batch_no', 'like', '%' . $obj['batch_no'] . '%');
        }

        if ($mode === 'expiry') {
            $q->whereNotNull('product_variation_batches.expiry_date');
            if ($expired_only) {
                $q->whereDate('product_variation_batches.expiry_date', '<', $now);
            } elseif ($expiry_within_days !== null) {
                $q->whereDate('product_variation_batches.expiry_date', '>=', $now)
                    ->whereDate('product_variation_batches.expiry_date', '<=', $now->copy()->addDays($expiry_within_days));
            }
        }

        $only_positive = ($obj['only_positive'] ?? '1') !== '0';
        if ($only_positive) {
            $q->where('product_variation_batches.quantity', '>', 0);
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'product_variation_batches.business_id', 'warehouses.branch_id');

        return $q->orderBy('product_variation_batches.expiry_date')->get([
            'product_variation_batches.product_variation_batch_id',
            'product_variation_batches.batch_no',
            'product_variation_batches.product_id',
            'product_variation_batches.product_variation_id',
            'product_variation_batches.warehouse_id',
            'product_variation_batches.business_id',
            'product_variation_batches.quantity',
            'product_variation_batches.avg_price',
            'product_variation_batches.manufacturing_date',
            'product_variation_batches.expiry_date',
            'product_variation_batches.status',
            'products.name as product_name',
            'product_variations.name as variation_name',
            'warehouses.name as warehouse_name',
            'branches.name as branch_name',
        ])->map(function ($row) use ($now) {
            $qty = (float) $row->quantity;
            $avg = (float) $row->avg_price;
            $days_to_expiry = $row->expiry_date
                ? (int) $now->diffInDays(Carbon::parse($row->expiry_date)->startOfDay(), false)
                : null;

            return (object) [
                'batch_no' => $row->batch_no,
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'warehouse_name' => $row->warehouse_name,
                'branch_name' => $row->branch_name,
                'quantity' => $qty,
                'avg_price' => $avg,
                'stock_value' => round($qty * $avg, 2),
                'manufacturing_date' => $row->manufacturing_date,
                'expiry_date' => $row->expiry_date,
                'days_to_expiry' => $days_to_expiry,
                'status' => $row->status,
                'ledger_url' => url('/admin/reports/stock-ledger') . '?' . http_build_query([
                    'product_id' => $row->product_id,
                    'product_variation_id' => $row->product_variation_id,
                    'warehouse_id' => $row->warehouse_id,
                    'business_id' => $row->business_id,
                ]),
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);
        $totals = [
            'total_qty' => decimal(round($rows->sum('quantity'), 3)),
            'total_value' => currency(round($rows->sum('stock_value'), 2)),
        ];

        return DataTables::of($rows)
            ->addColumn('batch_no', fn ($row) => e($row->batch_no))
            ->addColumn('product_name', fn ($row) => '<a href="' . e($row->ledger_url) . '">' . e($row->product_name) . '</a>')
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('branch_name', fn ($row) => e($row->branch_name ?? '-'))
            ->addColumn('quantity', fn ($row) => decimal($row->quantity))
            ->addColumn('avg_price', fn ($row) => currency($row->avg_price))
            ->addColumn('stock_value', fn ($row) => currency($row->stock_value))
            ->addColumn('manufacturing_date', fn ($row) => $row->manufacturing_date ? localDate($row->manufacturing_date) : '-')
            ->addColumn('expiry_date', fn ($row) => $row->expiry_date ? localDate($row->expiry_date) : '-')
            ->addColumn('days_to_expiry', function ($row) {
                if ($row->days_to_expiry === null) {
                    return '-';
                }
                return $row->days_to_expiry < 0
                    ? ('Expired ' . abs($row->days_to_expiry) . 'd')
                    : ($row->days_to_expiry . 'd');
            })
            ->addColumn('status', fn ($row) => e(ucfirst((string) $row->status)))
            ->rawColumns(['product_name'])
            ->with($totals)
            ->make(true);
    }
}
