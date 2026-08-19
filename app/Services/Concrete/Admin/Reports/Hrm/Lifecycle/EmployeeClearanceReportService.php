<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Lifecycle;

use App\Models\EmployeeExit;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * ExitClearance has no business_id/branch_id of its own, so scoping is
 * applied on its parent EmployeeExit (same idiom the base scope() helper
 * uses elsewhere) and the clearance rows are flattened out of the loaded
 * relation afterwards.
 */
class EmployeeClearanceReportService extends BaseLifecycleReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = EmployeeExit::with(['employee.user', 'employee.department', 'clearances.clearedBy'])
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        $query = $this->scope($query);

        $rows = collect();
        foreach ($query->get() as $exit) {
            foreach ($exit->clearances as $clearance) {
                if (!empty($filters['area']) && $clearance->area != $filters['area']) {
                    continue;
                }
                if (!empty($filters['status']) && $clearance->status != $filters['status']) {
                    continue;
                }

                $rows->push((object) [
                    'employee_code' => $exit->employee?->employee_code,
                    'name' => $exit->employee?->user?->name ?? '-',
                    'department' => $exit->employee?->department?->name ?? '-',
                    'exit_type' => ucfirst($exit->type),
                    'area' => $clearance->area,
                    'status' => $clearance->status,
                    'cleared_by' => $clearance->clearedBy?->name,
                    'cleared_at' => $clearance->cleared_at,
                    'remarks' => $clearance->remarks,
                ]);
            }
        }

        return $rows;
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->addColumn('cleared_at', fn ($row) => $row->cleared_at ? localDate($row->cleared_at) : '-')
            ->make(true);
    }
}
