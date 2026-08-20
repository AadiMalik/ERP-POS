<?php

namespace App\Services\ImportExport\Modules\Attendance;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use Illuminate\Database\Eloquent\Builder;

class AttendanceImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'attendance';
    }

    public function label(): string
    {
        return 'Attendance';
    }

    public function modelClass(): string
    {
        return Attendance::class;
    }

    public function primaryKey(): string
    {
        return 'attendance_id';
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
                key: 'Date',
                attribute: 'date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-20', '2026-08-20'],
                exportAccessor: 'date',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: true,
                enumValues: ['present', 'absent', 'late', 'half_day', 'on_leave', 'holiday'],
                sampleValues: ['present', 'absent'],
                exportAccessor: 'status',
            ),
            new ColumnDefinition(
                key: 'Check In',
                attribute: 'check_in_time',
                type: 'string',
                required: false,
                sampleValues: ['09:00', ''],
                exportAccessor: 'check_in_time',
                notes: 'Format HH:MM (24-hour). Leave blank if not applicable (e.g. Absent/On Leave/Holiday).',
            ),
            new ColumnDefinition(
                key: 'Check Out',
                attribute: 'check_out_time',
                type: 'string',
                required: false,
                sampleValues: ['17:00', ''],
                exportAccessor: 'check_out_time',
                notes: 'Format HH:MM (24-hour). Leave blank if not applicable (e.g. Absent/On Leave/Holiday). Working Hours/Late/Early-Leave minutes are not computed by import; they are left at their default (0) and can be recalculated via a manual edit.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['employee_id', 'date'];
    }

    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        return ['source' => 'manual'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Attendance::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'employee.user'];
    }
}
