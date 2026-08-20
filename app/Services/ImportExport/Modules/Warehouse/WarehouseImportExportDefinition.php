<?php

namespace App\Services\ImportExport\Modules\Warehouse;

use App\Models\Branch;
use App\Models\Warehouse;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use Illuminate\Database\Eloquent\Builder;

class WarehouseImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'warehouse';
    }

    public function label(): string
    {
        return 'Warehouses';
    }

    public function modelClass(): string
    {
        return Warehouse::class;
    }

    public function primaryKey(): string
    {
        return 'warehouse_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Main Store', 'Branch Store'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Branch',
                attribute: 'branch_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Branch::class, 'branch', 'name', scopeToBusiness: true),
                sampleValues: ['Main Branch', ''],
                exportAccessor: fn ($m) => $m->branch->name ?? '',
                notes: 'Leave blank to use your own branch. Copy the exact Branch Name from the Branches list.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['name'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Warehouse::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch'];
    }
}
