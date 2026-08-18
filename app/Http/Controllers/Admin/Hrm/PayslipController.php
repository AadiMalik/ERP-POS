<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:payslip.view')->only(['show']);
        $this->middleware('permission:payslip.print')->only(['pdf']);
    }

    public function show($payslip_id)
    {
        $payslip = Payslip::with(['employee.user', 'employee.department', 'employee.designation', 'items', 'payrollRun'])->findOrFail($payslip_id);
        return view('admin.hrm.payslip.show', compact('payslip'));
    }

    public function pdf($payslip_id)
    {
        $payslip = Payslip::with(['employee.user', 'employee.department', 'employee.designation', 'items', 'payrollRun'])->findOrFail($payslip_id);

        return Pdf::loadView('admin.hrm.payslip.pdf', compact('payslip'))
            ->setPaper('a4', 'portrait')
            ->stream('payslip-' . ($payslip->employee->employee_code ?? $payslip_id) . '.pdf');
    }
}
