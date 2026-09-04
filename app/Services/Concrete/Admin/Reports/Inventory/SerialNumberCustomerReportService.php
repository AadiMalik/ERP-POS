<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Enums\SerialStatus;
use App\Models\ProductVariationSerialNumber;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Customer-wise Serial Numbers - every sold unit, grouped/filterable by
 * customer, with its order and warranty status.
 */
class SerialNumberCustomerReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        return $this->query($obj)->get()->map(function ($row) {
            return (object) [
                'serial_no' => $row->serial_no,
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'customer_name' => $row->customer_name ?? '-',
                'order_daily_id' => $row->daily_order_id ?? '-',
                'warranty_expires_at' => $row->warranty_expires_at,
                'date_created' => $row->date_updated ?? $row->date_created,
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        return DataTables::of($rows)
            ->addColumn('customer_name', fn ($row) => e($row->customer_name))
            ->addColumn('serial_no', fn ($row) => e($row->serial_no))
            ->addColumn('product_name', fn ($row) => e($row->product_name))
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('order_daily_id', fn ($row) => e((string) $row->order_daily_id))
            ->addColumn('date_created', fn ($row) => $row->date_created ? localDate($row->date_created) : '-')
            ->addColumn('warranty_expires_at', fn ($row) => $row->warranty_expires_at ? localDate($row->warranty_expires_at) : '-')
            ->make(true);
    }

    protected function query(array $obj)
    {
        $filters = $this->baseFilters($obj);

        $q = ProductVariationSerialNumber::query()
            ->join('users', 'users.id', '=', 'product_variation_serial_numbers.current_customer_id')
            ->leftJoin('products', 'products.product_id', '=', 'product_variation_serial_numbers.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_serial_numbers.product_variation_id')
            ->leftJoin('orders', 'orders.order_id', '=', 'product_variation_serial_numbers.current_order_id')
            ->where('product_variation_serial_numbers.is_deleted', 0)
            ->where('product_variation_serial_numbers.status', SerialStatus::SOLD);

        if (!empty($filters['business_id'])) {
            $q->where('product_variation_serial_numbers.business_id', $filters['business_id']);
        }
        if (!empty($obj['customer_id'])) {
            $q->where('product_variation_serial_numbers.current_customer_id', $obj['customer_id']);
        }
        if (!empty($filters['product_id'])) {
            $q->where('product_variation_serial_numbers.product_id', $filters['product_id']);
        }
        if (!empty($filters['product_variation_id'])) {
            $q->where('product_variation_serial_numbers.product_variation_id', $filters['product_variation_id']);
        }
        if (!empty($obj['serial_no'])) {
            $q->where('product_variation_serial_numbers.serial_no', 'like', '%' . $obj['serial_no'] . '%');
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'product_variation_serial_numbers.business_id', 'product_variation_serial_numbers.branch_id');

        return $q->orderBy('users.name')
            ->orderBy('product_variation_serial_numbers.date_updated', 'desc')
            ->select([
                'product_variation_serial_numbers.serial_no',
                'product_variation_serial_numbers.warranty_expires_at',
                'product_variation_serial_numbers.date_created',
                'product_variation_serial_numbers.date_updated',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'users.name as customer_name',
                'orders.daily_order_id',
            ]);
    }
}
