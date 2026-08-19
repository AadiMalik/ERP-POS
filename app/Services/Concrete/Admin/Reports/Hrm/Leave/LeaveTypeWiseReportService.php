<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Leave;

use App\Models\LeaveType;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class LeaveTypeWiseReportService extends BaseLeaveReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $year = $filters['year'] ?? now()->year;

        $query = LeaveType::where('is_deleted', 0)
            ->withCount([
                'leaveRequests as total_requests' => fn ($q) => $q->where('is_deleted', 0)->whereYear('start_date', $year),
                'leaveRequests as approved_requests' => fn ($q) => $q->where('is_deleted', 0)->whereYear('start_date', $year)->where('status', 'approved'),
                'leaveRequests as pending_requests' => fn ($q) => $q->where('is_deleted', 0)->whereYear('start_date', $year)->where('status', 'pending'),
                'leaveRequests as rejected_requests' => fn ($q) => $q->where('is_deleted', 0)->whereYear('start_date', $year)->where('status', 'rejected'),
            ])
            ->withSum(['leaveRequests as total_days' => fn ($q) => $q->where('is_deleted', 0)->whereYear('start_date', $year)->where('status', 'approved')], 'days_count');

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['leave_type_id'])) {
            $query->where('leave_type_id', $filters['leave_type_id']);
        }

        $query = $this->scope($query);

        return $query->orderBy('name')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('leave_type', fn ($row) => $row->name)
            ->addColumn('total_days', fn ($row) => $row->total_days ?? 0)
            ->make(true);
    }
}
