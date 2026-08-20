<?php

namespace App\Services\ImportExport\Modules\EmployeeAdvance;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use Illuminate\Database\Eloquent\Builder;

/**
 * There is no natural unique business key for an advance (each request is
 * its own record) - uniqueKeyColumns() below uses a best-effort composite
 * of employee+date+amount so re-importing the identical file updates the
 * same row instead of duplicating it, while two genuinely different
 * advances (different date and/or amount) correctly create separate rows.
 * A same-day, same-amount second advance for the same employee will be
 * treated as an update of the first, not a new request - a known
 * limitation of this best-effort key.
 */
class EmployeeAdvanceImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'employee-advance';
    }

    public function label(): string
    {
        return 'Employee Advances';
    }

    public function modelClass(): string
    {
        return EmployeeAdvance::class;
    }

    public function primaryKey(): string
    {
        return 'employee_advance_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Employee Code',
                attribute: 'employee_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Employee::class, 'employee', 'employee_code', scopeToBusiness: true),
                sampleValues: ['EMP-0001', 'EMP-0002'],
                exportAccessor: fn ($m) => $m->employee->employee_code ?? '',
                notes: 'Copy the exact Employee Code from the Employees list.',
            ),
            new ColumnDefinition(
                key: 'Amount',
                attribute: 'amount',
                type: 'decimal',
                required: true,
                sampleValues: ['5000', '10000'],
                exportAccessor: 'amount',
            ),
            new ColumnDefinition(
                key: 'Request Date',
                attribute: 'request_date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-20', '2026-08-20'],
                exportAccessor: 'request_date',
            ),
            new ColumnDefinition(
                key: 'Description',
                attribute: 'reason',
                type: 'string',
                required: false,
                sampleValues: ['Medical expense', ''],
                exportAccessor: 'reason',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['employee_id', 'request_date', 'amount'];
    }

    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        return ['status' => 'pending'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = EmployeeAdvance::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('request_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['employee.user'];
    }
}
