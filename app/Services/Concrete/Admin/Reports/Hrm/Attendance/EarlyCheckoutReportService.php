<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Attendance;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EarlyCheckoutReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        [$start, $end] = $this->calendarRange($filters);

        $query = Attendance::with(['employee.user', 'employee.department'])
            ->where('is_deleted', 0)
            ->where('early_leave_minutes', '>', 0)
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

        return $query->orderBy('early_leave_minutes', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        $totals = ['total_early_checkout_minutes' => $rows->sum('early_leave_minutes')];

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('date', fn ($row) => businessDate($row->date))
            ->with($totals)
            ->make(true);
    }
}
