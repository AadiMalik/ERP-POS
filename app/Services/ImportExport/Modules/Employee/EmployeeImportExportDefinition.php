<?php

namespace App\Services\ImportExport\Modules\Employee;

use App\Enums\EmployeeStatus;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

/**
 * Employee import links to an ALREADY-EXISTING Admin User account (via the
 * Email column, resolved against User.email globally - not business-scoped,
 * since email is unique system-wide) rather than creating a new User. This
 * mirrors the read-only "link" side of EmployeeService::save(), not its
 * User::create() branch - see the custom Email-not-found message below.
 */
class EmployeeImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'employee';
    }

    public function label(): string
    {
        return 'Employees';
    }

    public function modelClass(): string
    {
        return Employee::class;
    }

    public function primaryKey(): string
    {
        return 'employee_id';
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
                attribute: 'employee_code',
                type: 'string',
                required: true,
                sampleValues: ['EMP-0001', 'EMP-0002'],
                exportAccessor: 'employee_code',
            ),
            new ColumnDefinition(
                key: 'Email',
                attribute: 'user_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(User::class, 'user', 'email', scopeToBusiness: false, relatedLabel: 'Admin User'),
                sampleValues: ['jane.doe@example.com', 'john.smith@example.com'],
                exportAccessor: fn ($m) => $m->user->email ?? '',
                notes: 'Must already exist as an Admin User (Employee import does not create a new user account). Create the user first via Admin Users, or import Admin Users first.',
            ),
            new ColumnDefinition(
                key: 'Department',
                attribute: 'department_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Department::class, 'department', 'name', scopeToBusiness: true),
                sampleValues: ['Sales', ''],
                exportAccessor: fn ($m) => $m->department->name ?? '',
                notes: 'Copy the exact Department Name from the Departments list.',
            ),
            new ColumnDefinition(
                key: 'Designation',
                attribute: 'designation_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Designation::class, 'designation', 'name', scopeToBusiness: true),
                sampleValues: ['Sales Executive', ''],
                exportAccessor: fn ($m) => $m->designation->name ?? '',
                notes: 'Copy the exact Designation Name from the Designations list.',
            ),
            new ColumnDefinition(
                key: 'Shift',
                attribute: 'shift_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Shift::class, 'shift', 'name', scopeToBusiness: true),
                sampleValues: ['Morning Shift', ''],
                exportAccessor: fn ($m) => $m->shift->name ?? '',
                notes: 'Copy the exact Shift Name from the Shifts list.',
            ),
            new ColumnDefinition(
                key: 'Joining Date',
                attribute: 'joining_date',
                type: 'date',
                required: false,
                sampleValues: ['2026-01-15', ''],
                exportAccessor: 'joining_date',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: false,
                enumValues: EmployeeStatus::manuallySettable(),
                sampleValues: ['active', ''],
                exportAccessor: 'status',
                notes: 'Defaults to "active" if left blank. Resigned/Terminated cannot be set via import - use the Resignation/Termination workflow.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['employee_code'];
    }

    /**
     * The generic RelationSpec "not found" message says 'found for this
     * business', which is misleading for a User lookup (User.email is
     * global, not business-scoped) - replace it with guidance specific to
     * this module's "link an existing Admin User" rule.
     */
    protected function applyDomainValidation(ResolvedRow $row, ImportContext $ctx): void
    {
        foreach ($row->errors as &$error) {
            if ($error['column'] === 'Email' && str_contains($error['reason'], 'found for this business')) {
                $error['reason'] = 'No Admin User found with this email. Create the user first via Admin Users (or import Admin Users first), then import Employees.';
            }
        }
        unset($error);
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Employee::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('employee_code');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'user', 'department', 'designation', 'shift'];
    }
}
