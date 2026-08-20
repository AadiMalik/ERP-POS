<?php

namespace App\Services\ImportExport\Modules\Designation;

use App\Models\Department;
use App\Models\Designation;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use Illuminate\Database\Eloquent\Builder;

class DesignationImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'designation';
    }

    public function label(): string
    {
        return 'Designations';
    }

    public function modelClass(): string
    {
        return Designation::class;
    }

    public function primaryKey(): string
    {
        return 'designation_id';
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
                sampleValues: ['HR Manager', 'Accountant'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Department',
                attribute: 'department_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Department::class, 'department', 'name', scopeToBusiness: true),
                sampleValues: ['Human Resources', 'Finance'],
                exportAccessor: fn ($m) => $m->department->name ?? '',
                notes: 'Copy the exact Name from the Departments list.',
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
        $query = Designation::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'department'];
    }
}
