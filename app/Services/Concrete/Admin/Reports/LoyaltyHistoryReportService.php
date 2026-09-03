<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\RoleNames;
use App\Models\CustomerLoyaltyTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Full customer loyalty ledger (earned/reserved/released/consumed/reversed/
 * adjusted/expired), read straight off customer_loyalty_transactions. Unlike
 * the Orders report family this is not order-table-rooted (many customers,
 * not one order per row), so it does not extend BaseOrderReportService -
 * mirrors CustomerLedgerReportService's role/permission intent for the same
 * customer-side report family instead. available_balance_after is written
 * per-row at post time by LoyaltyPointService, so the running balance column
 * is read directly here - never recomputed.
 *
 * customer_loyalty_transactions has no branch_id of its own, so branch-level
 * role scoping borrows customer_profiles.branch_id via a join on
 * customer_id = user_id. Like CustomerLedgerReportService, no branch filter
 * is exposed in the UI - the join exists only so BRANCHADMIN/POSMANAGER/etc.
 * are correctly restricted to their own branch's customers.
 */
class LoyaltyHistoryReportService
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

    public function build(array $obj): Collection
    {
        $query = CustomerLoyaltyTransaction::query()
            ->leftJoin('users', 'users.id', '=', 'customer_loyalty_transactions.customer_id')
            ->leftJoin('customer_profiles', 'customer_profiles.user_id', '=', 'customer_loyalty_transactions.customer_id')
            ->leftJoin('orders', function ($join) {
                $join->on('orders.order_id', '=', 'customer_loyalty_transactions.reference_id')
                    ->where('customer_loyalty_transactions.reference_type', '=', 'order');
            });

        $business_id = $obj['business_id'] ?? Auth::user()->business_id;

        if (!empty($business_id)) {
            $query->where('customer_loyalty_transactions.business_id', $business_id);
        }
        if (!empty($obj['user_id'])) {
            $query->where('customer_loyalty_transactions.customer_id', $obj['user_id']);
        }
        if (!empty($obj['start_date'])) {
            $query->where('customer_loyalty_transactions.date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }
        if (!empty($obj['end_date'])) {
            $query->where('customer_loyalty_transactions.date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        applyRoleScope($query, $this->allow_roles, 'customer_loyalty_transactions.business_id', 'customer_profiles.branch_id');

        return $query->orderBy('customer_loyalty_transactions.date_created')
            ->get([
                'customer_loyalty_transactions.customer_loyalty_transaction_id',
                'customer_loyalty_transactions.transaction_type',
                'customer_loyalty_transactions.points',
                'customer_loyalty_transactions.monetary_value',
                'customer_loyalty_transactions.available_balance_after',
                'customer_loyalty_transactions.reference_type',
                'customer_loyalty_transactions.reference_id',
                'customer_loyalty_transactions.description',
                'customer_loyalty_transactions.date_created',
                'users.name as customer_name',
                'orders.order_id as reference_order_id',
                'orders.daily_order_id as reference_order_no',
            ]);
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        return DataTables::of($rows)
            ->addColumn('customer_name', fn ($row) => $row->customer_name ?? 'N/A')
            ->addColumn('transaction_type', fn ($row) => ucfirst($row->transaction_type))
            ->editColumn('points', fn ($row) => decimal($row->points))
            ->editColumn('monetary_value', fn ($row) => $row->monetary_value !== null ? currency($row->monetary_value) : '-')
            ->addColumn('reference', fn ($row) => $this->referenceLabel($row))
            ->addColumn('date_created', fn ($row) => optional($row->date_created)->format('d-m-Y H:i'))
            ->editColumn('available_balance_after', fn ($row) => decimal($row->available_balance_after))
            ->rawColumns(['transaction_type', 'reference', 'date_created'])
            ->make(true);
    }

    protected function referenceLabel($row): string
    {
        if ('order' === $row->reference_type && !empty($row->reference_order_id)) {
            return "<a target='_blank' href='" . route('order.print', $row->reference_order_id) . "'>" . ($row->reference_order_no ?? $row->reference_id) . '</a>';
        }

        if (empty($row->reference_type)) {
            return 'N/A';
        }

        return ucfirst(str_replace('_', ' ', $row->reference_type)) . (!empty($row->reference_id) ? ' #' . $row->reference_id : '');
    }
}
