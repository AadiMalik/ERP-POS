<?php

namespace App\Services\Concrete\Admin\Reports;

use App\Enums\JournalSourceTypes;
use App\Enums\Status;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Engine for the Customer Aging report - the sales-side counterpart to
 * AccountsPayableInvoiceService, but simpler by design: unlike a Purchase
 * (which may be staged across multiple GRNs with payments allocated FIFO
 * across them), every posted Order already carries its own authoritative,
 * continuously-maintained paid_amount (kept in sync by
 * CustomerPaymentService::applyPosting()/reversePosting()), so no payment
 * allocation step is needed - each posted, not-fully-paid, non-returned
 * Order simply IS one "invoice" row.
 */
class CustomerAgingInvoiceService
{
    /**
     * $filters: business_id, branch_id, user_id, allow_roles (array)
     */
    public function getInvoices(array $filters = []): Collection
    {
        $query = Order::query()
            ->join('journal_entries', function ($join) {
                $join->on('journal_entries.source_id', '=', 'orders.order_id')
                    ->where('journal_entries.source_type', JournalSourceTypes::POS_SALE)
                    ->where('journal_entries.is_deleted', 0)
                    ->where('journal_entries.status', Status::POSTED);
            })
            ->leftJoin('customer_profiles', function ($join) {
                $join->on('customer_profiles.user_id', '=', 'orders.user_id')
                    ->whereColumn('customer_profiles.business_id', 'orders.business_id');
            })
            ->leftJoin('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.is_deleted', 0)
            ->where('orders.status', 'posted')
            ->whereColumn('orders.paid_amount', '<', 'orders.total');

        if (!empty($filters['business_id'])) {
            $query->where('orders.business_id', $filters['business_id']);
        }

        if (!empty($filters['branch_id'])) {
            $query->where('orders.branch_id', $filters['branch_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('orders.user_id', $filters['user_id']);
        }

        applyRoleScope($query, $filters['allow_roles'] ?? [], 'orders.business_id', 'orders.branch_id');

        return $query->orderBy('orders.sale_date')
            ->get([
                'orders.order_id',
                'orders.daily_order_id as invoice_number',
                'orders.sale_date as invoice_date',
                'orders.business_id',
                'orders.branch_id',
                'orders.user_id',
                'orders.total',
                'orders.paid_amount',
                'customer_profiles.credit_days',
                'customer_profiles.code as customer_code',
                'users.name as customer_name',
            ])
            ->map(function ($order) {
                $order->outstanding_amount = round((float) $order->total - (float) $order->paid_amount, 2);
                $order->due_date = Carbon::parse($order->invoice_date)->addDays((int) ($order->credit_days ?? 0));

                return $order;
            });
    }
}
