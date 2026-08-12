<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\Status;
use App\Models\JournalEntryDetail;
use App\Models\PurchaseReturn;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared query layer for the Purchase Return Summary and Purchase Return
 * Detail reports. baseQuery() returns one row per purchase_return_details
 * line (joined with its header, supplier, warehouse, branch, product and
 * variation) so both a line-level Detail report and an aggregated Summary
 * report can be built from the same filtered dataset.
 */
class PurchaseReturnQueryService
{
    public function baseQuery(array $filters): Builder
    {
        $query = PurchaseReturn::query()
            ->join('purchase_return_details', 'purchase_return_details.purchase_return_id', '=', 'purchase_returns.purchase_return_id')
            ->join('suppliers', 'suppliers.supplier_id', '=', 'purchase_returns.supplier_id')
            ->join('warehouses', 'warehouses.warehouse_id', '=', 'purchase_returns.warehouse_id')
            ->join('products', 'products.product_id', '=', 'purchase_return_details.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'purchase_return_details.product_variation_id')
            ->leftJoin('branches', 'branches.branch_id', '=', 'purchase_returns.branch_id')
            ->where('purchase_returns.is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('purchase_returns.business_id', $filters['business_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('purchase_returns.branch_id', $filters['branch_id']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('purchase_returns.supplier_id', $filters['supplier_id']);
        }

        if (!empty($filters['warehouse_id'])) {
            $query->where('purchase_returns.warehouse_id', $filters['warehouse_id']);
        }

        if (!empty($filters['product_id'])) {
            $query->where('purchase_return_details.product_id', $filters['product_id']);
        }

        if (!empty($filters['product_variation_id'])) {
            $query->where('purchase_return_details.product_variation_id', $filters['product_variation_id']);
        }

        if (!empty($filters['category_id'])) {
            $query->where('products.category_id', $filters['category_id']);
        }

        if (!empty($filters['brand_id'])) {
            $query->where('products.brand_id', $filters['brand_id']);
        }

        if (!empty($filters['return_type'])) {
            $query->where('purchase_returns.return_type', $filters['return_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('purchase_returns.status', $filters['status']);
        }

        if (!empty($filters['start_date'])) {
            $query->where('purchase_returns.purchase_return_date', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }

        if (!empty($filters['end_date'])) {
            $query->where('purchase_returns.purchase_return_date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        return applyRoleScope($query, $filters['allow_roles'] ?? [], 'purchase_returns.business_id', 'purchase_returns.branch_id');
    }

    /**
     * IDs of purchase returns with an active posted Purchase Return Voucher
     * (source_type = PURCHASE_RETURN), the same join shape as
     * AccountsPayableInvoiceService::fetchPurchaseReturns(), but returning
     * the set of posted return ids rather than a per-GRN debit amount.
     */
    public function postedMap(array $filters): Collection
    {
        $query = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->join('purchase_returns', 'purchase_returns.purchase_return_id', '=', 'journal_entries.source_id')
            ->where('journal_entries.source_type', JournalSourceTypes::PURCHASE_RETURN)
            ->where('journal_entries.status', Status::POSTED)
            ->where('journal_entries.is_deleted', 0)
            ->where('journal_entry_details.debit', '>', 0)
            ->whereColumn('journal_entry_details.supplier_id', 'purchase_returns.supplier_id');

        if (!empty($filters['business_id'])) {
            $query->where('journal_entries.business_id', $filters['business_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('journal_entries.branch_id', $filters['branch_id']);
        }

        return $query->pluck('purchase_returns.purchase_return_id');
    }
}
