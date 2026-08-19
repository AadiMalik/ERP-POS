<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Leave;

use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class DepartmentWiseLeaveReportService extends BaseLeaveReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $year = $filters['year'] ?? now()->year;

        $query = LeaveRequest::with(['employee.department'])
            ->where('is_deleted', 0)
            ->whereYear('start_date', $year);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }
        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        $query = $this->scope($query);

        return $query->get()
            ->groupBy(fn ($row) => $row->employee?->department?->department_id ?? 'unassigned')
            ->map(function ($group) {
                $department = $group->first()->employee?->department;

                return (object) [
                    'department' => $department?->name ?? 'Unassigned',
                    'total_requests' => $group->count(),
                    'approved_requests' => $group->where('status', 'approved')->count(),
                    'pending_requests' => $group->where('status', 'pending')->count(),
                    'rejected_requests' => $group->where('status', 'rejected')->count(),
                    'total_days' => $group->where('status', 'approved')->sum('days_count'),
                ];
            })
            ->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)->make(true);
    }
}
