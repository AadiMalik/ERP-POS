<?php

namespace App\Http\Controllers\Admin\Hrm\Ess;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class EssPayslipController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:ess.payslip.view');
    }

    protected function employee()
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            abort(403, 'No employee profile is linked to your account. Please contact HR.');
        }
        return $employee;
    }

    public function index()
    {
        $employee = $this->employee();
        $payslips = Payslip::with(['payrollRun'])
            ->where('employee_id', $employee->employee_id)
            ->orderBy('date_created', 'desc')
            ->get();

        return view('admin.hrm.ess.payslip.index', compact('payslips'));
    }

    public function show($payslip_id)
    {
        $payslip = $this->ownedPayslip($payslip_id);
        return view('admin.hrm.ess.payslip.show', compact('payslip'));
    }

    public function pdf($payslip_id)
    {
        $payslip = $this->ownedPayslip($payslip_id);

        return Pdf::loadView('admin.hrm.payslip.pdf', compact('payslip'))
            ->setPaper('a4', 'portrait')
            ->stream('payslip-' . $payslip_id . '.pdf');
    }

    protected function ownedPayslip($payslip_id)
    {
        $payslip = Payslip::with(['employee.user', 'employee.department', 'employee.designation', 'items', 'payrollRun'])->findOrFail($payslip_id);
        if ($payslip->employee_id != $this->employee()->employee_id) {
            abort(403);
        }
        return $payslip;
    }
}
