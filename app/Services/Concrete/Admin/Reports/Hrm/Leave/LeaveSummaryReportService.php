<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Leave;

use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class LeaveSummaryReportService extends BaseLeaveReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $year = $filters['year'] ?? now()->year;

        $query = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType'])
            ->where('is_deleted', 0)
            ->whereYear('start_date', $year);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }

        $query = $this->scope($query);

        return $query->get()
            ->groupBy(fn ($row) => $row->employee_id . '|' . $row->leave_type_id)
            ->map(function ($group) {
                $first = $group->first();
                $entitlement = $first->leaveType?->max_days_per_year ?? 0;
                $used = $group->where('status', 'approved')->sum('days_count');

                return (object) [
                    'employee_code' => $first->employee?->employee_code,
                    'name' => $first->employee?->user?->name ?? '-',
                    'department' => $first->employee?->department?->name ?? '-',
                    'leave_type' => $first->leaveType?->name ?? '-',
                    'entitlement' => $entitlement,
                    'used' => $used,
                    'pending' => $group->where('status', 'pending')->sum('days_count'),
                    'approved' => $used,
                    'rejected' => $group->where('status', 'rejected')->sum('days_count'),
                    'remaining' => max(0, $entitlement - $used),
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
