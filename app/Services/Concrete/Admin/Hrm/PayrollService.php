<?php

namespace App\Services\Concrete\Admin\Hrm;

use App\Models\Employee;
use App\Models\Payslip;
use App\Models\PayslipItem;
use App\Models\PayrollRun;
use App\Traits\Auditable;
use App\Traits\Notifiable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * The payroll generation engine. generate() is idempotent/re-runnable while
 * a run is 'draft' (recomputes every payslip from current attendance/leave/
 * structure/deduction/advance data, never mutating those source records).
 * finalize() only locks the numbers (status change, no side effects) so it
 * can be safely reopened. pay() is the one irreversible step that actually
 * decrements advance balances and writes salary ledger entries - mirroring
 * how a real payroll only moves money once, at disbursement.
 */
class PayrollService
{
    use Auditable, Notifiable;

    protected $attendance_service;
    protected $leave_request_service;
    protected $salary_structure_service;
    protected $deduction_service;
    protected $advance_service;
    protected $ledger_service;

    public function __construct(
        AttendanceService $attendance_service,
        LeaveRequestService $leave_request_service,
        EmployeeSalaryStructureService $salary_structure_service,
        EmployeeDeductionService $deduction_service,
        EmployeeAdvanceService $advance_service,
        EmployeeLedgerService $ledger_service
    ) {
        $this->attendance_service = $attendance_service;
        $this->leave_request_service = $leave_request_service;
        $this->salary_structure_service = $salary_structure_service;
        $this->deduction_service = $deduction_service;
        $this->advance_service = $advance_service;
        $this->ledger_service = $ledger_service;
    }

    public function getData($obj)
    {
        $datatable = PayrollRun::where('is_deleted', 0)->orderBy('year', 'desc')->orderBy('month', 'desc');
        $datatable = applyRoleScope($datatable);

        return DataTables::of($datatable)
            ->addColumn('period', function ($item) {
                return date('F', mktime(0, 0, 0, $item->month, 1)) . ' ' . $item->year;
            })
            ->addColumn('status', function ($item) {
                $map = ['draft' => 'warning', 'finalized' => 'info', 'paid' => 'success'];
                return '<span class="badge bg-label-' . ($map[$item->status] ?? 'secondary') . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "<a class='btn btn-icon btn-outline-primary' href='" . route('payroll.show', $item->payroll_run_id) . "'><i class='fa fa-eye'></i></a>";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function generate($month, $year, $branch_id = null)
    {
        $business_id = Auth::user()->business_id;

        $run = PayrollRun::where('business_id', $business_id)
            ->where('branch_id', $branch_id)
            ->where('month', $month)
            ->where('year', $year)
            ->where('is_deleted', 0)
            ->first();

        if ($run && $run->status != 'draft') {
            throw new Exception('This payroll run has already been finalized. Use Reopen to make changes.');
        }

        DB::beginTransaction();
        try {
            if (!$run) {
                $run = PayrollRun::create([
                    'payroll_run_id' => generateUuid(),
                    'month' => $month,
                    'year' => $year,
                    'status' => 'draft',
                    'business_id' => $business_id,
                    'branch_id' => $branch_id,
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);
            }

            // Re-runnable: wipe and recompute every payslip for this draft run.
            $existing_ids = Payslip::where('payroll_run_id', $run->payroll_run_id)->pluck('payslip_id');
            PayslipItem::whereIn('payslip_id', $existing_ids)->delete();
            Payslip::where('payroll_run_id', $run->payroll_run_id)->delete();

            $employees_query = Employee::where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->whereIn('status', ['active', 'on_leave']);
            if ($branch_id) {
                $employees_query->where('branch_id', $branch_id);
            }
            $employees = $employees_query->get();

            $total_amount = 0;

            foreach ($employees as $employee) {
                $structure = $this->salary_structure_service->getCurrent($employee->employee_id);
                if (!$structure) {
                    continue; // no salary structure assigned yet - skip until HR assigns one
                }

                $payslip = $this->buildPayslip($run, $employee, $structure, $month, $year);
                $total_amount += $payslip->net_salary;
            }

            $run->update([
                'total_amount' => $total_amount,
                'generated_at' => now(),
            ]);

            DB::commit();
            return $run;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function buildPayslip($run, Employee $employee, $structure, $month, $year)
    {
        $resolved = $this->salary_structure_service->resolveComponents($structure);
        $attendance = $this->attendance_service->monthlySummary($employee->employee_id, $year, $month);
        $leave = $this->leave_request_service->approvedDaysInMonth($employee->employee_id, $year, $month);
        $adhoc_deductions = $this->deduction_service->activeForMonth($employee->employee_id, $year, $month);
        $advances = $this->advance_service->getActiveForEmployee($employee->employee_id);

        $days_in_month = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        $per_day_rate = $days_in_month > 0 ? $structure->basic_salary / $days_in_month : 0;

        $overtime_amount = round($attendance['overtime_hours'] * ($structure->overtime_rate_per_hour ?? 0), 2);
        $unpaid_leave_deduction = round($leave['unpaid_days'] * $per_day_rate, 2);
        $absent_deduction = round($attendance['absent_days'] * $per_day_rate, 2);

        $structure_earnings_total = array_sum(array_column($resolved['earnings'], 'amount'));
        $structure_deductions_total = array_sum(array_column($resolved['deductions'], 'amount'));
        $adhoc_deductions_total = $adhoc_deductions->sum('amount');

        $advance_deduction_total = 0;
        $advance_items = [];
        foreach ($advances as $advance) {
            $amount = min($advance->installment_amount, $advance->remaining_balance);
            if ($amount > 0) {
                $advance_deduction_total += $amount;
                $advance_items[] = ['name' => 'Advance Recovery', 'amount' => $amount];
            }
        }

        $total_earnings = round($structure->basic_salary + $structure_earnings_total + $overtime_amount, 2);
        $total_deductions = round($structure_deductions_total + $adhoc_deductions_total + $unpaid_leave_deduction + $absent_deduction + $advance_deduction_total, 2);
        $net_salary = round($total_earnings - $total_deductions, 2);

        $payslip = Payslip::create([
            'payslip_id' => generateUuid(),
            'payroll_run_id' => $run->payroll_run_id,
            'employee_id' => $employee->employee_id,
            'basic_salary' => $structure->basic_salary,
            'total_earnings' => $total_earnings,
            'total_deductions' => $total_deductions,
            'overtime_amount' => $overtime_amount,
            'advance_deduction' => $advance_deduction_total,
            'net_salary' => $net_salary,
            'present_days' => $attendance['present_days'],
            'absent_days' => $attendance['absent_days'],
            'leave_days' => $attendance['leave_days'],
            'status' => 'generated',
            'business_id' => $employee->business_id,
            'branch_id' => $employee->branch_id,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        $items = array_merge(
            [['name' => 'Basic Salary', 'amount' => $structure->basic_salary, 'type' => 'earning']],
            array_map(fn ($e) => $e + ['type' => 'earning'], $resolved['earnings']),
            $overtime_amount > 0 ? [['name' => 'Overtime', 'amount' => $overtime_amount, 'type' => 'earning']] : [],
            array_map(fn ($d) => $d + ['type' => 'deduction'], $resolved['deductions']),
            $unpaid_leave_deduction > 0 ? [['name' => 'Unpaid Leave Deduction', 'amount' => $unpaid_leave_deduction, 'type' => 'deduction']] : [],
            $absent_deduction > 0 ? [['name' => 'Absence Deduction', 'amount' => $absent_deduction, 'type' => 'deduction']] : [],
            $adhoc_deductions->map(fn ($d) => ['name' => $d->title, 'amount' => $d->amount, 'type' => 'deduction'])->all(),
            array_map(fn ($a) => $a + ['type' => 'deduction'], $advance_items)
        );

        foreach ($items as $item) {
            PayslipItem::create([
                'payslip_item_id' => generateUuid(),
                'payslip_id' => $payslip->payslip_id,
                'component_name' => $item['name'],
                'type' => $item['type'],
                'amount' => $item['amount'],
            ]);
        }

        return $payslip;
    }

    public function getById($payroll_run_id)
    {
        return PayrollRun::with(['payslips.employee.user'])->findOrFail($payroll_run_id);
    }

    public function finalize($payroll_run_id)
    {
        $run = PayrollRun::findOrFail($payroll_run_id);
        if ($run->status != 'draft') {
            throw new Exception('Only draft payroll runs can be finalized.');
        }
        if ($run->payslips()->count() == 0) {
            throw new Exception('Generate the payroll before finalizing.');
        }

        $run->update(['status' => 'finalized', 'finalized_at' => now()]);
        $this->logActivity('payroll', $payroll_run_id, 'finalized', null, ['total_amount' => $run->total_amount], null, $run->business_id, $run->branch_id);
        $this->notify('payroll_finalized', $run->business_id, $run->branch_id, 'Payroll Finalized', 'Payroll for ' . date('F', mktime(0, 0, 0, $run->month, 1)) . ' ' . $run->year . ' has been finalized.', 'payroll', $payroll_run_id, route('payroll.show', $payroll_run_id));
    }

    public function reopen($payroll_run_id)
    {
        $run = PayrollRun::findOrFail($payroll_run_id);
        if ($run->status != 'finalized') {
            throw new Exception('Only finalized (not yet paid) payroll runs can be reopened.');
        }

        $run->update(['status' => 'draft', 'finalized_at' => null]);
        $this->logActivity('payroll', $payroll_run_id, 'reopened');
    }

    /**
     * Terminal action: actually recovers advance installments (decrementing
     * remaining_balance) and writes the employee ledger's salary entries.
     * Never call twice for the same run - status flips to 'paid' precisely
     * to prevent that.
     */
    public function pay($payroll_run_id)
    {
        $run = PayrollRun::with(['payslips.employee'])->findOrFail($payroll_run_id);
        if ($run->status != 'finalized') {
            throw new Exception('Only finalized payroll runs can be marked as paid.');
        }

        DB::beginTransaction();
        try {
            foreach ($run->payslips as $payslip) {
                $payslip->update(['status' => 'paid', 'paid_at' => now()]);

                if ($payslip->advance_deduction > 0) {
                    $remaining_to_apply = $payslip->advance_deduction;
                    $advances = $this->advance_service->getActiveForEmployee($payslip->employee_id);
                    foreach ($advances as $advance) {
                        if ($remaining_to_apply <= 0) {
                            break;
                        }
                        $amount = min($advance->installment_amount, $advance->remaining_balance, $remaining_to_apply);
                        if ($amount > 0) {
                            $this->advance_service->deductInstallment($advance, $amount, $payslip->payslip_id);
                            $remaining_to_apply -= $amount;
                        }
                    }
                }

                $this->ledger_service->addEntry(
                    $payslip->employee_id,
                    'salary',
                    0,
                    0,
                    'Net salary paid for ' . date('F', mktime(0, 0, 0, $run->month, 1)) . ' ' . $run->year . ': ' . number_format($payslip->net_salary, 2),
                    'payslip',
                    $payslip->payslip_id,
                    $payslip->business_id
                );
            }

            $run->update(['status' => 'paid']);
            $this->logActivity('payroll', $payroll_run_id, 'paid', null, ['total_amount' => $run->total_amount], null, $run->business_id, $run->branch_id);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
