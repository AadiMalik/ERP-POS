<?php

namespace App\Http\Controllers\Admin\Hrm\Ess;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\AttendanceService;
use App\Services\Concrete\Admin\Hrm\LeaveRequestService;
use Illuminate\Support\Facades\Auth;

class EssDashboardController extends Controller
{
    protected $attendance_service;
    protected $leave_request_service;

    public function __construct(AttendanceService $attendance_service, LeaveRequestService $leave_request_service)
    {
        $this->middleware('permission:ess.dashboard.view');

        $this->attendance_service = $attendance_service;
        $this->leave_request_service = $leave_request_service;
    }

    public function index()
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            abort(403, 'No employee profile is linked to your account. Please contact HR.');
        }

        $today_attendance = $this->attendance_service->today($employee->employee_id);
        $monthly_summary = $this->attendance_service->monthlySummary($employee->employee_id, now()->year, now()->month);
        $recent_leaves = $this->leave_request_service->getByEmployee($employee->employee_id)->take(5);

        return view('admin.hrm.ess.dashboard', compact('employee', 'today_attendance', 'monthly_summary', 'recent_leaves'));
    }
}
