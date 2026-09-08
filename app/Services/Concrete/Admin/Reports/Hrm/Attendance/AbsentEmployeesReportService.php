<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class AbsentEmployeesReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        [$start, $end] = $this->calendarRange($filters);

        $query = Attendance::with(['employee.user', 'employee.department'])
            ->where('is_deleted', 0)
            ->where('status', 'absent')
            ->whereBetween('date', [$start, $end]);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        $query = $this->scope($query);

        return $query->orderBy('date', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('date', fn ($row) => businessDate($row->date))
            ->addColumn('notes', fn ($row) => $row->notes ?? '-')
            ->make(true);
    }
}
