<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared query layer over the central product_variation_stock_transactions
 * ledger - the single append-only source of truth for every posted stock
 * movement (Opening Stock, Purchase/GRN, Purchase Return, Transfer In/Out,
 * Stock Taking, etc). Every writer only inserts a row here at the moment its
 * document is approved (and soft-deletes it on reversal), so any active
 * (is_deleted = 0) row is inherently "posted" - there is no separate
 * posted/unposted flag to filter on for this table.
 */
class StockLedgerQueryService
{
    public function baseQuery(array $filters): Builder
    {
        $query = ProductVariationStockTransaction::query()
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'product_variation_stock_transactions.warehouse_id')
            ->join('products', 'products.product_id', '=', 'product_variation_stock_transactions.product_id')
            ->join('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_stock_transactions.product_variation_id')
            ->where('product_variation_stock_transactions.is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('product_variation_stock_transactions.business_id', $filters['business_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('warehouses.branch_id', $filters['branch_id']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('product_variation_stock_transactions.warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('product_variation_stock_transactions.product_id', $filters['product_id']);
        }

        if (!empty($filters['product_variation_id'])) {
            $query->where('product_variation_stock_transactions.product_variation_id', $filters['product_variation_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('products.brand_id', $filters['brand_id']);
        }

        if (!empty($filters['transaction_type'])) {
            $query->where('product_variation_stock_transactions.transaction_type', $filters['transaction_type']);
        }

        if (!empty($filters['reference_type'])) {
            $query->where('product_variation_stock_transactions.reference_type', $filters['reference_type']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('product_variation_stock_transactions.transaction_date', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }

        if (!empty($filters['end_date'])) {
            $query->where('product_variation_stock_transactions.transaction_date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        return applyRoleScope($query, $filters['allow_roles'] ?? [], 'product_variation_stock_transactions.business_id', 'warehouses.branch_id');
    }

    public function transactions(array $filters): Collection
    {
        return $this->baseQuery($filters)
            ->orderBy('product_variation_stock_transactions.transaction_date')
            ->orderBy('product_variation_stock_transactions.date_created')
            ->orderBy('product_variation_stock_transactions.product_variation_stock_transaction_id')
            ->get([
                'product_variation_stock_transactions.product_variation_stock_transaction_id',
                'product_variation_stock_transactions.transaction_date',
                'product_variation_stock_transactions.transaction_type',
                'product_variation_stock_transactions.business_id',
                'product_variation_stock_transactions.product_id',
                'product_variation_stock_transactions.product_variation_id',
                'product_variation_stock_transactions.warehouse_id',
                'product_variation_stock_transactions.unit_id',
                'product_variation_stock_transactions.base_quantity',
                'product_variation_stock_transactions.unit_price',
                'product_variation_stock_transactions.total_price',
                'product_variation_stock_transactions.quantity_after',
                'product_variation_stock_transactions.avg_price_after',
                'product_variation_stock_transactions.reference_id',
                'product_variation_stock_transactions.reference_type',
                'product_variation_stock_transactions.remarks',
                'warehouses.name as warehouse_name',
                'warehouses.branch_id',
                'products.name as product_name',
                'products.category_id',
                'products.brand_id',
                'product_variations.name as variation_name',
            ]);
    }

    /**
     * Live opening/closing balance for exactly one product+variation+warehouse.
     * Opening balance is the quantity_after of the last active transaction
     * strictly before the report period's start date (quantity_after is
     * already a running-balance snapshot, so no replay/summing is needed);
     * with no start date the report spans all history, so there is
     * definitionally nothing preceding it. Closing balance is simply the
     * live ProductVariationStock snapshot. Only meaningful when filters
     * narrow to a single item - broader filters span multiple items and
     * have no single balance to show.
     */
    public function singleItemBalance(array $filters): ?array
    {
        if (empty($filters['business_id']) || empty($filters['warehouse_id']) || empty($filters['product_id']) || empty($filters['product_variation_id'])) {
            return null;
        }

        $stock = ProductVariationStock::where('business_id', $filters['business_id'])
            ->where('warehouse_id', $filters['warehouse_id'])
            ->where('product_id', $filters['product_id'])
            ->where('product_variation_id', $filters['product_variation_id'])
            ->first();

        $opening = 0.0;

        if (!empty($filters['start_date'])) {
            $openingQuery = $this->baseQuery(array_merge($filters, ['start_date' => null, 'end_date' => null]))
                ->where('product_variation_stock_transactions.transaction_date', '<', Carbon::parse($filters['start_date'])->startOfDay());

            $opening = (float) ($openingQuery
                ->orderByDesc('product_variation_stock_transactions.transaction_date')
                ->orderByDesc('product_variation_stock_transactions.date_created')
                ->value('product_variation_stock_transactions.quantity_after') ?? 0);
        }

        return [
            'opening_balance'   => $opening,
            'closing_balance'   => (float) ($stock->quantity ?? 0),
            'current_avg_price' => (float) ($stock->avg_price ?? 0),
            'reserved_quantity' => (float) ($stock->reserved_quantity ?? 0),
            'available_quantity' => (float) ($stock->quantity ?? 0) - (float) ($stock->reserved_quantity ?? 0),
        ];
    }
}
