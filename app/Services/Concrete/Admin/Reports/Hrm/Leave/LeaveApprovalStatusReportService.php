<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Leave;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class LeaveApprovalStatusReportService extends BaseLeaveReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType', 'approver'])
            ->where('is_deleted', 0)
            ->whereIn('status', ['approved', 'rejected']);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('approved_at', '>=', Carbon::parse($filters['start_date'])->toDateString());
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('approved_at', '<=', Carbon::parse($filters['end_date'])->toDateString());
        }

        $query = $this->scope($query);

        return $query->orderBy('approved_at', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('leave_type', fn ($row) => $row->leaveType?->name ?? '-')
            ->addColumn('approver', fn ($row) => $row->approver?->name ?? '-')
            ->addColumn('approved_at', fn ($row) => $row->approved_at ? localDate($row->approved_at) : '-')
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->make(true);
    }
}
