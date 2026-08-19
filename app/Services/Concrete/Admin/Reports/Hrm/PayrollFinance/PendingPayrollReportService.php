<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\PayrollFinance;

use App\Models\PayrollRun;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

/**
 * Rows = every month up to the current one for the selected year that is
 * either missing a PayrollRun entirely ("Not Generated") or has one still in
 * draft/not-yet-finalized status - the two "incomplete payroll" cases the
 * task description calls out.
 */
class PendingPayrollReportService extends BasePayrollFinanceReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);
        $year = $filters['year'] ?? now()->year;

        $query = PayrollRun::where('is_deleted', 0)->where('year', $year);
        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }
        $query = $this->scope($query);
        $runs = $query->get()->keyBy('month');

        $lastMonth = $year == now()->year ? now()->month : 12;

        $rows = collect();
        for ($month = 1; $month <= $lastMonth; $month++) {
            $run = $runs->get($month);

            if (!$run) {
                $rows->push((object) [
                    'period' => date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year,
                    'status' => 'Not Generated',
                    'generated_at' => null,
                    'employee_count' => 0,
                ]);
            } elseif ($run->status != 'paid') {
                $rows->push((object) [
                    'period' => date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year,
                    'status' => ucfirst($run->status),
                    'generated_at' => $run->generated_at,
                    'employee_count' => $run->payslips()->count(),
                ]);
            }
        }

        return $rows;
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('generated_at', fn ($row) => $row->generated_at ? localDate($row->generated_at) : '-')
            ->make(true);
    }
}
