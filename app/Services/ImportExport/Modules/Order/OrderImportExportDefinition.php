<?php

namespace App\Services\ImportExport\Modules\Order;

use App\Enums\Status;
use App\Models\Order;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * EXPORT ONLY - explicitly no Import support. Order's real write path
 * (OrderService::save()/post()/void()) does stock deduction, tax/discount
 * recalculation and general-ledger posting, none of which can be safely
 * replayed from a flat bulk-Excel row in this pass, so canImport() is
 * hard-disabled below.
 *
 * Every column is declared with relation: null, required: false,
 * type: 'string' - irrelevant to a disabled import path, kept only so every
 * ColumnDefinition here stays consistent with the shared interface. Values
 * are rendered purely via exportAccessor closures.
 *
 * "Customer" reads $order->user (not a Customer model) - customers were
 * merged into the users table (see 2026_08_14_130009_backfill_customers_into_users
 * and 2026_08_14_130010_drop_customer_id_columns migrations); Order's
 * customer_id column was dropped in favour of user_id, matching
 * OrderService::getData()'s own 'customer' column.
 *
 * "Payment Status" has no stored column - it's derived from paid_amount vs
 * total, replicating the exact same rule OrderService::getData() uses for
 * its own payment_status DataTable column (and the thermal receipt).
 */
class OrderImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'order';
    }

    public function label(): string
    {
        return 'Orders';
    }

    public function modelClass(): string
    {
        return Order::class;
    }

    public function primaryKey(): string
    {
        return 'order_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function canImport(): bool
    {
        return false;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Order No',
                attribute: 'daily_order_id',
                type: 'string',
                required: false,
                relation: null,
                exportAccessor: 'daily_order_id',
            ),
            new ColumnDefinition(
                key: 'Date',
                attribute: 'order_date',
                type: 'string',
                required: false,
                relation: null,
                exportAccessor: 'order_date',
            ),
            new ColumnDefinition(
                key: 'Customer',
                attribute: 'customer_display_only',
                type: 'string',
                required: false,
                relation: null,
                exportAccessor: fn ($m) => $m->user->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Warehouse',
                attribute: 'warehouse_display_only',
                type: 'string',
                required: false,
                relation: null,
                exportAccessor: fn ($m) => $m->warehouse->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'string',
                required: false,
                relation: null,
                exportAccessor: 'status',
            ),
            new ColumnDefinition(
                key: 'Total Amount',
                attribute: 'total',
                type: 'string',
                required: false,
                relation: null,
                exportAccessor: 'total',
            ),
            new ColumnDefinition(
                key: 'Discount',
                attribute: 'discount_display_only',
                type: 'string',
                required: false,
                relation: null,
                exportAccessor: fn ($m) => $m->discount->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Payment Status',
                attribute: 'payment_status_display_only',
                type: 'string',
                required: false,
                relation: null,
                exportAccessor: function ($m) {
                    $due = max(($m->total ?? 0) - ($m->paid_amount ?? 0), 0);

                    if ($due <= 0) {
                        return Status::PAID;
                    }

                    return ($m->paid_amount ?? 0) > 0 ? Status::PARTIALLY_PAID : Status::UNPAID;
                },
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        // Unreachable - canImport() is false, so the generic engine never
        // calls resolveRow()/save() for this module.
        return [];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Order::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('order_date', '>=', Carbon::parse($filters['start_date'])->startOfDay());
        }
        if (!empty($filters['end_date'])) {
            $query->where('order_date', '<=', Carbon::parse($filters['end_date'])->endOfDay());
        }

        return $query->orderBy('order_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'warehouse', 'user', 'discount'];
    }
}
