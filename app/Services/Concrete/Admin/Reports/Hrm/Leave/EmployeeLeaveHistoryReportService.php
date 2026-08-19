<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Leave;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EmployeeLeaveHistoryReportService extends BaseLeaveReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = LeaveRequest::with(['employee.user', 'leaveType', 'approver'])
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('start_date', '>=', Carbon::parse($filters['start_date'])->toDateString());
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('end_date', '<=', Carbon::parse($filters['end_date'])->toDateString());
        }

        $query = $this->scope($query);

        return $query->orderBy('start_date', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('leave_type', fn ($row) => $row->leaveType?->name ?? '-')
            ->addColumn('start_date', fn ($row) => localDate($row->start_date))
            ->addColumn('end_date', fn ($row) => localDate($row->end_date))
            ->addColumn('approver', fn ($row) => $row->approver?->name ?? '-')
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->make(true);
    }
}
