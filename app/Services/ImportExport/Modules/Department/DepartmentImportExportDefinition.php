<?php

namespace App\Services\ImportExport\Modules\Department;

use App\Models\Branch;
use App\Models\Department;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use Illuminate\Database\Eloquent\Builder;

class DepartmentImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'department';
    }

    public function label(): string
    {
        return 'Departments';
    }

    public function modelClass(): string
    {
        return Department::class;
    }

    public function primaryKey(): string
    {
        return 'department_id';
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
                sampleValues: ['Human Resources', 'Finance'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Branch',
                attribute: 'branch_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Branch::class, 'branch', 'name', scopeToBusiness: true),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->branch->name ?? '',
                notes: 'Leave blank to use your own branch. Copy the exact Branch Name from the Branches list.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['name'];
    }

    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        return ['status' => 'active'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Department::query()->where('is_deleted', 0);

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
