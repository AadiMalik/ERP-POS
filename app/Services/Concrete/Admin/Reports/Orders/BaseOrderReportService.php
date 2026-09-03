<?php

namespace App\Services\Concrete\Admin\Reports\Orders;

use App\Enums\RoleNames;
use App\Enums\Status;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Shared filter / due / payment-status helpers for every Orders report.
 * Concrete services implement build() (raw rows for print/pdf/export) and
 * getData() (DataTables JSON). applyCommonFilters() only applies plain WHERE
 * clauses (never select/groupBy) so it is safe to call before aggregated
 * select()/groupBy() on the same query builder. Column names default to the
 * orders table; pass $columns overrides when the query joins and qualifies
 * columns (e.g. 'orders.business_id'). Due is always computed, never stored.
 */
abstract class BaseOrderReportService
{
    protected array $allow_roles = [
        RoleNames::SUPERADMIN,
        RoleNames::BUSINESSADMIN,
        RoleNames::BRANCHADMIN,
        RoleNames::GENERALMANAGER,
        RoleNames::OPERATIONMANAGER,
        RoleNames::SALEMANAGER,
        RoleNames::FINANCEMANAGER,
        RoleNames::ACCOUNTANT,
        RoleNames::REPORTINGANALYST,
    ];

    abstract public function build(array $obj): Collection;

    abstract public function getData(array $obj);

    /**
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  array<string, string>  $columns  Keys: business, branch, date, order_source, status, customer, product, variation
     */
    protected function applyCommonFilters($query, array $obj, array $columns = []): void
    {
        $cols = array_merge([
            'business'     => 'business_id',
            'branch'       => 'branch_id',
            'date'         => 'sale_date',
            'order_source' => 'order_source_id',
            'status'       => 'status',
            'customer'     => 'user_id',
            'product'      => 'product_id',
            'variation'    => 'product_variation_id',
        ], $columns);

        $business_id = $obj['business_id'] ?? Auth::user()->business_id;

        if (!empty($business_id) && !empty($cols['business'])) {
            $query->where($cols['business'], $business_id);
        }
        if (!empty($obj['branch_id']) && !empty($cols['branch'])) {
            $query->where($cols['branch'], $obj['branch_id']);
        }
        if (!empty($obj['order_source_id']) && !empty($cols['order_source'])) {
            $query->where($cols['order_source'], $obj['order_source_id']);
        }
        if (!empty($obj['status']) && !empty($cols['status'])) {
            $query->where($cols['status'], $obj['status']);
        }
        if (!empty($obj['user_id']) && !empty($cols['customer'])) {
            $query->where($cols['customer'], $obj['user_id']);
        }
        if (!empty($obj['product_id']) && !empty($cols['product'])) {
            $query->where($cols['product'], $obj['product_id']);
        }
        if (!empty($obj['product_variation_id']) && !empty($cols['variation'])) {
            $query->where($cols['variation'], $obj['product_variation_id']);
        }
        if (!empty($obj['start_date']) && !empty($cols['date'])) {
            $query->where($cols['date'], '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date']) && !empty($cols['date'])) {
            $query->where($cols['date'], '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        applyRoleScope(
            $query,
            $this->allow_roles,
            $cols['business'] ?? 'business_id',
            $cols['branch'] ?? 'branch_id'
        );
    }

    protected function dueOf($total, $paid): float
    {
        return max((float) $total - (float) $paid, 0);
    }

    protected function paymentStatusOf($total, $paid): string
    {
        $due = $this->dueOf($total, $paid);

        if ($due <= 0) {
            return Status::PAID;
        }
        if ((float) $paid > 0) {
            return Status::PARTIALLY_PAID;
        }

        return Status::UNPAID;
    }

    protected function filterByPaymentStatus(Collection $rows, ?string $status, string $totalKey, string $paidKey): Collection
    {
        if (empty($status)) {
            return $rows;
        }

        return $rows->filter(
            fn ($row) => $this->paymentStatusOf($row->{$totalKey}, $row->{$paidKey}) === $status
        )->values();
    }
}
