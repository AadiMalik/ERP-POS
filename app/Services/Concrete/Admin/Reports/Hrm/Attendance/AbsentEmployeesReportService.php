<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Attendance;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class AbsentEmployeesReportService extends BaseAttendanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $start = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : Carbon::now()->startOfMonth();
        $end = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : Carbon::now()->endOfDay();

        $query = Attendance::with(['employee.user', 'employee.department'])
            ->where('is_deleted', 0)
            ->where('status', 'absent')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

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
            ->addColumn('date', fn ($row) => localDate($row->date))
            ->addColumn('notes', fn ($row) => $row->notes ?? '-')
            ->make(true);
    }
}
