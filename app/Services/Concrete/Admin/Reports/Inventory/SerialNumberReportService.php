<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Enums\SerialStatus;
use App\Models\ProductVariationSerialNumber;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Backs three reports off the same table (product_variation_serial_numbers),
 * distinguished only by a forced status filter: Serial Number Register (all
 * statuses), Available Serial Numbers (status=available), Sold Serial
 * Numbers (status=sold). $forcedStatus is passed by each report's
 * controller/service instantiation site.
 */
class SerialNumberReportService
{
    use AppliesInventoryReportScope;

    public function __construct(protected ?string $forcedStatus = null)
    {
    }

    public function build(array $obj): Collection
    {
        return $this->query($obj)->get()->map(function ($row) {
            return (object) [
                'product_variation_serial_number_id' => $row->product_variation_serial_number_id,
                'serial_no' => $row->serial_no,
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'warehouse_name' => $row->warehouse_name ?? '-',
                'status' => $row->status,
                'status_label' => SerialStatus::getOptions()[$row->status] ?? $row->status,
                'avg_price' => (float) $row->avg_price,
                'customer_name' => $row->customer_name ?? '-',
                'order_daily_id' => $row->daily_order_id ?? '-',
                'date_created' => $row->date_created,
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        return DataTables::of($rows)
            ->addColumn('serial_no', fn ($row) => e($row->serial_no))
            ->addColumn('product_name', fn ($row) => e($row->product_name))
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('warehouse_name', fn ($row) => e($row->warehouse_name))
            ->addColumn('status_label', fn ($row) => e($row->status_label))
            ->addColumn('avg_price', fn ($row) => currency($row->avg_price))
            ->addColumn('customer_name', fn ($row) => e($row->customer_name))
            ->addColumn('date_created', fn ($row) => $row->date_created ? localDate($row->date_created) : '-')
            ->make(true);
    }

    protected function query(array $obj)
    {
        $filters = $this->baseFilters($obj);

        $q = ProductVariationSerialNumber::query()
            ->leftJoin('products', 'products.product_id', '=', 'product_variation_serial_numbers.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_serial_numbers.product_variation_id')
            ->leftJoin('warehouses', 'warehouses.warehouse_id', '=', 'product_variation_serial_numbers.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'product_variation_serial_numbers.current_customer_id')
            ->leftJoin('orders', 'orders.order_id', '=', 'product_variation_serial_numbers.current_order_id')
            ->where('product_variation_serial_numbers.is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $q->where('product_variation_serial_numbers.business_id', $filters['business_id']);
        }
        if (!empty($filters['warehouse_id'])) {
            $q->where('product_variation_serial_numbers.warehouse_id', $filters['warehouse_id']);
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

        if ($this->forcedStatus) {
            $q->where('product_variation_serial_numbers.status', $this->forcedStatus);
        } elseif (!empty($obj['status'])) {
            $q->where('product_variation_serial_numbers.status', $obj['status']);
        }

        if (!empty($filters['start_date'])) {
            $q->whereDate('product_variation_serial_numbers.date_created', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $q->whereDate('product_variation_serial_numbers.date_created', '<=', $filters['end_date']);
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'product_variation_serial_numbers.business_id', 'product_variation_serial_numbers.branch_id');

        return $q->orderBy('product_variation_serial_numbers.date_created', 'desc')
            ->select([
                'product_variation_serial_numbers.product_variation_serial_number_id',
                'product_variation_serial_numbers.serial_no',
                'product_variation_serial_numbers.status',
                'product_variation_serial_numbers.avg_price',
                'product_variation_serial_numbers.date_created',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'warehouses.name as warehouse_name',
                'users.name as customer_name',
                'orders.daily_order_id',
            ]);
    }
}
