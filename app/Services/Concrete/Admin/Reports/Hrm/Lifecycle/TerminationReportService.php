<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Lifecycle;

use App\Models\EmployeeExit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class TerminationReportService extends BaseLifecycleReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = EmployeeExit::with(['employee.user', 'employee.department', 'approver'])
            ->where('is_deleted', 0)
            ->where('type', 'termination');

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', fn ($q) => $q->where('department_id', $filters['department_id']));
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->whereDate('request_date', '>=', Carbon::parse($filters['start_date'])->toDateString());
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('request_date', '<=', Carbon::parse($filters['end_date'])->toDateString());
        }

        $query = $this->scope($query);

        return $query->orderBy('request_date', 'desc')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('request_date', fn ($row) => localDate($row->request_date))
            ->addColumn('last_working_date', fn ($row) => localDate($row->last_working_date))
            ->addColumn('final_settlement_amount', fn ($row) => currency($row->final_settlement_amount))
            ->addColumn('approver', fn ($row) => $row->approver?->name ?? '-')
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->make(true);
    }
}
