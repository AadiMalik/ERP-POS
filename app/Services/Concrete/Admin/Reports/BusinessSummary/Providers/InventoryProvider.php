<?php

namespace App\Services\Concrete\Admin\Reports\BusinessSummary\Providers;

use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\CostPriceAdjustment;
use App\Models\ProductVariationBatch;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Models\TransferNote;
use App\Models\Warehouse;
use App\Services\Concrete\Admin\Dashboard\DashboardInventoryService;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryContext;
use App\Services\Concrete\Admin\Reports\BusinessSummary\BusinessSummaryInsightFactory as Insight;
use Illuminate\Support\Facades\DB;

class InventoryProvider
{
    public function __construct(protected DashboardInventoryService $dashboard_inventory_service)
    {
    }

    public function collect(BusinessSummaryContext $context): array
    {
        if (!$context->hasModule('inventory') || !$context->canAny([
            'reports.stock-summary.view',
            'stock.view',
            'warehouse.view',
        ])) {
            return ['kpis' => [], 'insights' => []];
        }

        $scope = [
            'business_id' => $context->business_id,
            'effective_branch_id' => $context->branch_id,
        ];
        $dashboard = $this->dashboard_inventory_service->build($scope);
        $negative = $this->negativeStock($context);
        $expiry = $this->expiringBatches($context);
        $waste = $this->wasteInPeriod($context);
        $idle = $this->idleStock($context);
        $transfers = $this->pendingTransfers($context);
        $adjustments = $this->adjustments($context);
        $warehouseRisks = $this->warehouseRisks($context);

        $kpis = [
            'stock_value' => $dashboard['stock_value'],
            'low_stock' => $dashboard['low_stock_count'],
            'out_of_stock' => $dashboard['out_of_stock_count'],
        ];

        $insights = [];

        $insights[] = Insight::make([
            'id' => 'stock_value',
            'module' => 'inventory',
            'severity' => 'informational',
            'icon' => 'fa-warehouse',
            'title_key' => 'reports.business_summary.stock_value_title',
            'title_params' => ['amount' => currency($dashboard['stock_value'])],
            'description_key' => 'reports.business_summary.stock_value_desc',
            'description_params' => [
                'in_stock' => $dashboard['in_stock_count'],
                'total' => $dashboard['total_products'],
            ],
            'metric' => $dashboard['stock_value'],
            'metric_type' => 'money',
            'value' => $dashboard['stock_value'],
            'action' => Insight::action($context, 'reports.business_summary.actions.view_stock_valuation', '/admin/reports/stock-valuation', [], 'reports.stock-valuation.view'),
        ]);

        if ($negative['count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'negative_stock',
                'module' => 'inventory',
                'severity' => 'critical',
                'icon' => 'fa-triangle-exclamation',
                'title_key' => 'reports.business_summary.negative_stock_title',
                'title_params' => ['count' => $negative['count']],
                'description_key' => 'reports.business_summary.negative_stock_desc',
                'description_params' => ['warehouses' => $negative['warehouses']],
                'metric' => $negative['count'],
                'impact' => abs($negative['value']),
                'value' => abs($negative['value']),
                'why_key' => 'reports.business_summary.why.negative_stock',
                'details' => $negative['details'],
                'action' => Insight::action($context, 'reports.business_summary.actions.view_stock_summary', '/admin/reports/stock-summary', ['report_mode' => 'availability'], 'reports.stock-summary.view'),
            ]);
        }

        if ($dashboard['out_of_stock_count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'out_of_stock',
                'module' => 'inventory',
                'severity' => $dashboard['out_of_stock_count'] >= 10 ? 'critical' : 'important',
                'icon' => 'fa-box-open',
                'title_key' => 'reports.business_summary.out_of_stock_title',
                'title_params' => ['count' => $dashboard['out_of_stock_count']],
                'description_key' => 'reports.business_summary.out_of_stock_desc',
                'metric' => $dashboard['out_of_stock_count'],
                'impact' => $dashboard['out_of_stock_count'],
                'why_key' => 'reports.business_summary.why.out_of_stock',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_stock_summary', '/admin/reports/stock-summary', ['report_mode' => 'low_stock'], 'reports.stock-summary.view'),
            ]);
        }

        if ($dashboard['low_stock_count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'low_stock',
                'module' => 'inventory',
                'severity' => 'important',
                'icon' => 'fa-boxes-stacked',
                'title_key' => 'reports.business_summary.low_stock_title',
                'title_params' => ['count' => $dashboard['low_stock_count']],
                'description_key' => 'reports.business_summary.low_stock_desc',
                'metric' => $dashboard['low_stock_count'],
                'impact' => $dashboard['low_stock_count'],
                'why_key' => 'reports.business_summary.why.low_stock',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_low_stock', '/admin/reports/stock-summary', ['report_mode' => 'low_stock'], 'reports.stock-summary.view'),
            ]);
        }

        if ($expiry['count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'expiry_risk',
                'module' => 'inventory',
                'severity' => $expiry['expired'] > 0 ? 'critical' : 'important',
                'icon' => 'fa-calendar-xmark',
                'title_key' => 'reports.business_summary.expiry_title',
                'title_params' => ['count' => $expiry['count']],
                'description_key' => 'reports.business_summary.expiry_desc',
                'description_params' => [
                    'expired' => $expiry['expired'],
                    'days' => $context->threshold('near_expiry_days', 30),
                    'value' => currency($expiry['value']),
                ],
                'metric' => $expiry['count'],
                'value' => $expiry['value'],
                'impact' => $expiry['value'],
                'why_key' => 'reports.business_summary.why.expiry',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_expiry', '/admin/reports/batch-expiry', [], 'reports.batch-expiry.view'),
            ]);
        }

        $wasteThreshold = ((float) $context->threshold('high_waste_percent_of_stock', 2) / 100) * max($dashboard['stock_value'], 1);
        if ($waste['count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'waste_loss',
                'module' => 'inventory',
                'severity' => $waste['value'] >= $wasteThreshold ? 'critical' : 'important',
                'icon' => 'fa-trash',
                'title_key' => 'reports.business_summary.waste_title',
                'title_params' => ['amount' => currency($waste['value'])],
                'description_key' => 'reports.business_summary.waste_desc',
                'description_params' => ['qty' => $waste['qty'], 'count' => $waste['count']],
                'metric' => $waste['value'],
                'metric_type' => 'money',
                'value' => $waste['value'],
                'impact' => $waste['value'],
                'why_key' => $waste['value'] >= $wasteThreshold ? 'reports.business_summary.why.high_waste' : 'reports.business_summary.why.waste',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_stock_loss', '/admin/reports/stock-loss', [], 'reports.stock-loss.view'),
            ]);
        }

        if ($idle['dead'] > 0) {
            $insights[] = Insight::make([
                'id' => 'dead_stock',
                'module' => 'inventory',
                'severity' => 'important',
                'icon' => 'fa-snowflake',
                'title_key' => 'reports.business_summary.dead_stock_title',
                'title_params' => ['count' => $idle['dead']],
                'description_key' => 'reports.business_summary.dead_stock_desc',
                'description_params' => [
                    'days' => $context->threshold('dead_stock_days', 90),
                    'value' => currency($idle['dead_value']),
                ],
                'metric' => $idle['dead'],
                'value' => $idle['dead_value'],
                'impact' => $idle['dead_value'],
                'why_key' => 'reports.business_summary.why.dead_stock',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_stock_aging', '/admin/reports/stock-aging', ['report_mode' => 'velocity', 'movement_class' => 'non_moving'], 'reports.stock-aging.view'),
            ]);
        }

        if ($idle['slow'] > 0) {
            $insights[] = Insight::make([
                'id' => 'slow_stock',
                'module' => 'inventory',
                'severity' => 'informational',
                'icon' => 'fa-hourglass-half',
                'title_key' => 'reports.business_summary.slow_stock_title',
                'title_params' => ['count' => $idle['slow']],
                'description_key' => 'reports.business_summary.slow_stock_desc',
                'description_params' => ['days' => $context->threshold('slow_moving_days', 30)],
                'metric' => $idle['slow'],
                'value' => $idle['slow_value'],
                'action' => Insight::action($context, 'reports.business_summary.actions.view_stock_aging', '/admin/reports/stock-aging', ['report_mode' => 'velocity', 'movement_class' => 'slow'], 'reports.stock-aging.view'),
            ]);
        }

        if ($transfers > 0) {
            $insights[] = Insight::make([
                'id' => 'pending_transfers',
                'module' => 'inventory',
                'severity' => 'important',
                'icon' => 'fa-truck',
                'title_key' => 'reports.business_summary.pending_transfers_title',
                'title_params' => ['count' => $transfers],
                'description_key' => 'reports.business_summary.pending_transfers_desc',
                'metric' => $transfers,
                'why_key' => 'reports.business_summary.why.pending_transfers',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_transfers', '/admin/transfer-note', [], 'transfer-note.view'),
            ]);
        }

        if ($adjustments['count'] > 0) {
            $insights[] = Insight::make([
                'id' => 'cost_adjustments',
                'module' => 'inventory',
                'severity' => 'informational',
                'icon' => 'fa-sliders',
                'title_key' => 'reports.business_summary.cost_adjustments_title',
                'title_params' => ['count' => $adjustments['count']],
                'description_key' => 'reports.business_summary.cost_adjustments_desc',
                'metric' => $adjustments['count'],
                'action' => Insight::action($context, 'reports.business_summary.actions.view_cost_adjustments', '/admin/reports/cost-price-adjustment', [], 'reports.cost-price-adjustment.view'),
            ]);
        }

        foreach ($warehouseRisks as $insight) {
            $insights[] = $insight;
        }

        if ($dashboard['out_of_stock_count'] === 0 && $negative['count'] === 0 && $dashboard['stock_value'] > 0) {
            $insights[] = Insight::make([
                'id' => 'stock_healthy',
                'module' => 'inventory',
                'severity' => 'good',
                'icon' => 'fa-circle-check',
                'title_key' => 'reports.business_summary.stock_healthy_title',
                'description_key' => 'reports.business_summary.stock_healthy_desc',
                'metric' => $dashboard['in_stock_count'],
            ]);
        }

        return compact('kpis', 'insights');
    }

    protected function warehouseIds(BusinessSummaryContext $context): ?array
    {
        if (!$context->branch_id) {
            return null;
        }

        return Warehouse::where('business_id', $context->business_id)
            ->where('branch_id', $context->branch_id)
            ->where('is_deleted', 0)
            ->pluck('warehouse_id')
            ->all();
    }

    protected function stockQuery(BusinessSummaryContext $context)
    {
        $warehouseIds = $this->warehouseIds($context);

        return ProductVariationStock::query()
            ->where('product_variation_stocks.business_id', $context->business_id)
            ->where('product_variation_stocks.is_deleted', 0)
            ->when($warehouseIds !== null, fn ($q) => $q->whereIn('product_variation_stocks.warehouse_id', $warehouseIds));
    }

    protected function negativeStock(BusinessSummaryContext $context): array
    {
        $rows = $this->stockQuery($context)
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'product_variation_stocks.warehouse_id')
            ->where('product_variation_stocks.quantity', '<', 0)
            ->select(
                'warehouses.name as warehouse_name',
                DB::raw('COUNT(*) as cnt'),
                DB::raw('COALESCE(SUM(product_variation_stocks.avg_price * product_variation_stocks.quantity), 0) as value')
            )
            ->groupBy('warehouses.warehouse_id', 'warehouses.name')
            ->get();

        return [
            'count' => (int) $rows->sum('cnt'),
            'value' => (float) $rows->sum('value'),
            'warehouses' => $rows->pluck('warehouse_name')->filter()->unique()->implode(', '),
            'details' => $rows->map(fn ($row) => [
                'label' => $row->warehouse_name,
                'value' => (string) $row->cnt,
            ])->all(),
        ];
    }

    protected function expiringBatches(BusinessSummaryContext $context): array
    {
        $days = (int) $context->threshold('near_expiry_days', 30);
        $today = businessToday();
        $until = \Carbon\Carbon::parse($today, $context->timezone)->addDays($days)->toDateString();
        $warehouseIds = $this->warehouseIds($context);

        $query = ProductVariationBatch::query()
            ->where('business_id', $context->business_id)
            ->where('is_deleted', 0)
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', $until)
            ->when($warehouseIds !== null, fn ($q) => $q->whereIn('warehouse_id', $warehouseIds));

        $count = (clone $query)->count();
        $expired = (clone $query)->whereDate('expiry_date', '<', $today)->count();
        $value = (float) (clone $query)->selectRaw('COALESCE(SUM(avg_price * quantity), 0) as v')->value('v');

        return ['count' => $count, 'expired' => $expired, 'value' => $value];
    }

    protected function wasteInPeriod(BusinessSummaryContext $context): array
    {
        $row = ProductVariationStockTransaction::query()
            ->where('business_id', $context->business_id)
            ->where('is_deleted', 0)
            ->whereIn('transaction_type', [TransactionType::DAMAGE, TransactionType::WASTAGE, TransactionType::EXPIRED])
            ->when($this->warehouseIds($context), fn ($q, $ids) => $q->whereIn('warehouse_id', $ids))
            ->whereBetween('transaction_date', [
                businessStartOfDay($context->start_date),
                businessEndOfDay($context->end_date),
            ])
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(ABS(base_quantity)), 0) as qty, COALESCE(SUM(ABS(base_quantity) * unit_price), 0) as value')
            ->first();

        return [
            'count' => (int) ($row->cnt ?? 0),
            'qty' => (float) ($row->qty ?? 0),
            'value' => (float) ($row->value ?? 0),
        ];
    }

    protected function idleStock(BusinessSummaryContext $context): array
    {
        $deadDays = (int) $context->threshold('dead_stock_days', 90);
        $slowDays = (int) $context->threshold('slow_moving_days', 30);
        $now = now($context->timezone);
        $warehouseIds = $this->warehouseIds($context);

        $lastTxn = DB::table('product_variation_stock_transactions')
            ->select('business_id', 'warehouse_id', 'product_variation_id', DB::raw('MAX(transaction_date) as last_movement_date'))
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->groupBy('business_id', 'warehouse_id', 'product_variation_id');

        $base = ProductVariationStock::query()
            ->leftJoinSub($lastTxn, 'last_txn', function ($join) {
                $join->on('last_txn.business_id', '=', 'product_variation_stocks.business_id')
                    ->on('last_txn.warehouse_id', '=', 'product_variation_stocks.warehouse_id')
                    ->on('last_txn.product_variation_id', '=', 'product_variation_stocks.product_variation_id');
            })
            ->where('product_variation_stocks.business_id', $context->business_id)
            ->where('product_variation_stocks.is_deleted', 0)
            ->where('product_variation_stocks.quantity', '>', 0)
            ->when($warehouseIds !== null, fn ($q) => $q->whereIn('product_variation_stocks.warehouse_id', $warehouseIds));

        $deadCutoff = $now->copy()->subDays($deadDays);
        $slowCutoff = $now->copy()->subDays($slowDays);

        $dead = (clone $base)->where(function ($q) use ($deadCutoff) {
            $q->whereNull('last_txn.last_movement_date')
                ->where('product_variation_stocks.date_created', '<=', $deadCutoff)
                ->orWhere('last_txn.last_movement_date', '<=', $deadCutoff);
        });
        $slow = (clone $base)->where(function ($q) use ($slowCutoff, $deadCutoff) {
            $q->whereRaw('COALESCE(last_txn.last_movement_date, product_variation_stocks.date_created) <= ?', [$slowCutoff])
                ->whereRaw('COALESCE(last_txn.last_movement_date, product_variation_stocks.date_created) > ?', [$deadCutoff]);
        });

        return [
            'dead' => (clone $dead)->count(),
            'dead_value' => (float) (clone $dead)->selectRaw('COALESCE(SUM(product_variation_stocks.avg_price * product_variation_stocks.quantity), 0) as v')->value('v'),
            'slow' => (clone $slow)->count(),
            'slow_value' => (float) (clone $slow)->selectRaw('COALESCE(SUM(product_variation_stocks.avg_price * product_variation_stocks.quantity), 0) as v')->value('v'),
        ];
    }

    protected function pendingTransfers(BusinessSummaryContext $context): int
    {
        return TransferNote::query()
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->when($context->branch_id, function ($q) use ($context) {
                $q->where(function ($inner) use ($context) {
                    $inner->where('branch_id', $context->branch_id)
                        ->orWhere('destination_branch_id', $context->branch_id);
                });
            })
            ->whereIn('status', [Status::DRAFT, Status::IN_TRANSIT])
            ->count();
    }

    protected function adjustments(BusinessSummaryContext $context): array
    {
        if (!class_exists(CostPriceAdjustment::class)) {
            return ['count' => 0];
        }

        $count = CostPriceAdjustment::query()
            ->where('is_deleted', 0)
            ->where('business_id', $context->business_id)
            ->when($context->branch_id, fn ($q) => $q->where('branch_id', $context->branch_id))
            ->whereBetween('adjustment_date', [
                businessStartOfDay($context->start_date),
                businessEndOfDay($context->end_date),
            ])
            ->count();

        return ['count' => $count];
    }

    protected function warehouseRisks(BusinessSummaryContext $context): array
    {
        $rows = $this->stockQuery($context)
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'product_variation_stocks.warehouse_id')
            ->select(
                'warehouses.warehouse_id',
                'warehouses.name as warehouse_name',
                DB::raw('COALESCE(SUM(product_variation_stocks.avg_price * product_variation_stocks.quantity), 0) as stock_value'),
                DB::raw('SUM(CASE WHEN product_variation_stocks.quantity < 0 THEN 1 ELSE 0 END) as negative_count'),
                DB::raw('SUM(CASE WHEN product_variation_stocks.quantity <= 0 THEN 1 ELSE 0 END) as out_count')
            )
            ->groupBy('warehouses.warehouse_id', 'warehouses.name')
            ->havingRaw('SUM(CASE WHEN product_variation_stocks.quantity < 0 THEN 1 ELSE 0 END) > 0 OR SUM(CASE WHEN product_variation_stocks.quantity <= 0 THEN 1 ELSE 0 END) > 0')
            ->orderByDesc('negative_count')
            ->limit(5)
            ->get();

        return $rows->map(function ($row) use ($context) {
            return Insight::make([
                'id' => 'warehouse_risk_' . $row->warehouse_id,
                'module' => 'inventory',
                'severity' => $row->negative_count > 0 ? 'critical' : 'important',
                'icon' => 'fa-warehouse',
                'title_key' => 'reports.business_summary.warehouse_risk_title',
                'title_params' => ['warehouse' => $row->warehouse_name],
                'description_key' => 'reports.business_summary.warehouse_risk_desc',
                'description_params' => [
                    'negative' => $row->negative_count,
                    'out' => $row->out_count,
                    'value' => currency($row->stock_value),
                ],
                'metric' => (int) $row->out_count,
                'value' => (float) $row->stock_value,
                'why_key' => 'reports.business_summary.why.warehouse_risk',
                'action' => Insight::action($context, 'reports.business_summary.actions.view_warehouse', '/admin/reports/stock-summary', ['warehouse_id' => $row->warehouse_id], 'reports.stock-summary.view'),
            ]);
        })->all();
    }
}
