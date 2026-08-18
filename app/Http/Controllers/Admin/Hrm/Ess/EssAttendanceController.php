<?php

namespace App\Http\Controllers\Admin\Hrm\Ess;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\AttendanceService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Support\Facades\Auth;

class EssAttendanceController extends Controller
{
    use ResponseAPI;

    protected $attendance_service;

    public function __construct(AttendanceService $attendance_service)
    {
        $this->middleware('permission:ess.attendance.manage');

        $this->attendance_service = $attendance_service;
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
        $attendances = \App\Models\Attendance::where('employee_id', $employee->employee_id)
            ->where('is_deleted', 0)
            ->orderBy('date', 'desc')
            ->paginate(20);

        return view('admin.hrm.ess.attendance', compact('attendances'));
    }

    public function checkIn()
    {
        try {
            $attendance = $this->attendance_service->checkIn($this->employee()->employee_id);
            return $this->success('Checked in successfully.', $attendance);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function checkOut()
    {
        try {
            $attendance = $this->attendance_service->checkOut($this->employee()->employee_id);
            return $this->success('Checked out successfully.', $attendance);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
