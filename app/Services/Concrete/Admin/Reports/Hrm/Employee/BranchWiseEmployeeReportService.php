<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Employee;

use App\Enums\EmployeeStatus;
use App\Models\Branch;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class BranchWiseEmployeeReportService extends BaseEmployeeReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = Branch::where('is_deleted', 0)
            ->withCount([
                'employees as total_employees' => fn ($q) => $q->where('is_deleted', 0),
                'employees as active_employees' => fn ($q) => $q->where('is_deleted', 0)->where('status', EmployeeStatus::ACTIVE),
                'employees as on_leave_employees' => fn ($q) => $q->where('is_deleted', 0)->where('status', EmployeeStatus::ON_LEAVE),
                'employees as resigned_employees' => fn ($q) => $q->where('is_deleted', 0)->where('status', EmployeeStatus::RESIGNED),
                'employees as terminated_employees' => fn ($q) => $q->where('is_deleted', 0)->where('status', EmployeeStatus::TERMINATED),
            ]);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        $query = $this->scope($query);

        return $query->orderBy('name')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('branch', fn ($row) => $row->name)
            ->make(true);
    }
}
