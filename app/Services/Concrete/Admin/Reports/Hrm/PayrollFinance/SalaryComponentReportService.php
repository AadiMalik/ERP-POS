<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\SalaryComponent;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class SalaryComponentReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = SalaryComponent::where('is_deleted', 0)
            ->withCount(['structureItems as usage_count']);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $query = $this->scope($query);

        return $query->orderBy('name')->get();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('type', fn ($row) => ucfirst($row->type))
            ->addColumn('calculation_type', fn ($row) => ucfirst(str_replace('_', ' ', $row->calculation_type)))
            ->addColumn('status', fn ($row) => ucfirst($row->status))
            ->make(true);
    }
}
