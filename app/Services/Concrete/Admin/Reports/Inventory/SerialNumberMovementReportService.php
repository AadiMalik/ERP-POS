<?php

namespace App\Services\Concrete\Admin\Reports\Inventory;

use App\Enums\SerialMovementEventType;
use App\Models\ProductVariationSerialMovement;
use App\Services\Concrete\Admin\Reports\Inventory\Concerns\AppliesInventoryReportScope;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Serial Number Movement History - the full append-only audit trail from
 * product_variation_serial_movements, across every unit.
 */
class SerialNumberMovementReportService
{
    use AppliesInventoryReportScope;

    public function build(array $obj): Collection
    {
        return $this->query($obj)->get()->map(function ($row) {
            return (object) [
                'serial_no' => $row->serial_no,
                'product_name' => $row->product_name,
                'variation_name' => $row->variation_name,
                'event_type' => $row->event_type,
                'event_label' => SerialMovementEventType::getOptions()[$row->event_type] ?? $row->event_type,
                'from_warehouse_name' => $row->from_warehouse_name ?? '-',
                'to_warehouse_name' => $row->to_warehouse_name ?? '-',
                'createdby_name' => $row->createdby_name ?? '-',
                'notes' => $row->notes,
                'date_created' => $row->date_created,
            ];
        });
    }

    public function getData(array $obj)
    {
        $rows = $this->build($obj);

        return DataTables::of($rows)
            ->addColumn('date_created', fn ($row) => $row->date_created ? localDate($row->date_created) : '-')
            ->addColumn('serial_no', fn ($row) => e($row->serial_no))
            ->addColumn('product_name', fn ($row) => e($row->product_name))
            ->addColumn('variation_name', fn ($row) => e($row->variation_name))
            ->addColumn('event_label', fn ($row) => e($row->event_label))
            ->addColumn('from_warehouse_name', fn ($row) => e($row->from_warehouse_name))
            ->addColumn('to_warehouse_name', fn ($row) => e($row->to_warehouse_name))
            ->addColumn('createdby_name', fn ($row) => e($row->createdby_name))
            ->addColumn('notes', fn ($row) => e($row->notes ?? '-'))
            ->make(true);
    }

    protected function query(array $obj)
    {
        $filters = $this->baseFilters($obj);

        $q = ProductVariationSerialMovement::query()
            ->join('product_variation_serial_numbers', 'product_variation_serial_numbers.product_variation_serial_number_id', '=', 'product_variation_serial_movements.product_variation_serial_number_id')
            ->leftJoin('products', 'products.product_id', '=', 'product_variation_serial_numbers.product_id')
            ->leftJoin('product_variations', 'product_variations.product_variation_id', '=', 'product_variation_serial_numbers.product_variation_id')
            ->leftJoin('warehouses as from_wh', 'from_wh.warehouse_id', '=', 'product_variation_serial_movements.from_warehouse_id')
            ->leftJoin('warehouses as to_wh', 'to_wh.warehouse_id', '=', 'product_variation_serial_movements.to_warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'product_variation_serial_movements.createdby_id');

        if (!empty($filters['business_id'])) {
            $q->where('product_variation_serial_movements.business_id', $filters['business_id']);
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
        if (!empty($obj['event_type'])) {
            $q->where('product_variation_serial_movements.event_type', $obj['event_type']);
        }
        if (!empty($filters['start_date'])) {
            $q->whereDate('product_variation_serial_movements.date_created', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $q->whereDate('product_variation_serial_movements.date_created', '<=', $filters['end_date']);
        }

        $q = applyRoleScope($q, $filters['allow_roles'], 'product_variation_serial_movements.business_id', 'product_variation_serial_movements.branch_id');

        return $q->orderBy('product_variation_serial_movements.date_created', 'desc')
            ->select([
                'product_variation_serial_movements.event_type',
                'product_variation_serial_movements.notes',
                'product_variation_serial_movements.date_created',
                'product_variation_serial_numbers.serial_no',
                'products.name as product_name',
                'product_variations.name as variation_name',
                'from_wh.name as from_warehouse_name',
                'to_wh.name as to_warehouse_name',
                'users.name as createdby_name',
            ]);
    }
}
