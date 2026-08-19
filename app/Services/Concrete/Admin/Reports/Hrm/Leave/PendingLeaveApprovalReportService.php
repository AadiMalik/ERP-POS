<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Leave;

use App\Models\LeaveRequest;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class PendingLeaveApprovalReportService extends BaseLeaveReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType'])
            ->where('is_deleted', 0)
            ->where('status', 'pending');

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

        return $query->orderBy('date_created', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('leave_type', fn ($row) => $row->leaveType?->name ?? '-')
            ->addColumn('start_date', fn ($row) => localDate($row->start_date))
            ->addColumn('end_date', fn ($row) => localDate($row->end_date))
            ->addColumn('requested_on', fn ($row) => localDate($row->date_created))
            ->make(true);
    }
}
