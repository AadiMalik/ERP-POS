<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Services\Concrete\Admin\Hrm\AttendanceService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    use ResponseAPI;
    use HandlesImportExport;

    protected $attendance_service;
    protected $employee_service;

    public function __construct(AttendanceService $attendance_service, EmployeeService $employee_service)
    {
        $this->middleware('permission:attendance.view')->only(['index', 'getData']);
        $this->middleware('permission:attendance.create')->only(['create']);
        $this->middleware('permission:attendance.create|attendance.edit')->only(['store']);
        $this->middleware('permission:attendance.edit')->only(['edit']);
        $this->middleware('permission:attendance.delete')->only(['destroy']);
        $this->middleware('permission:attendance.report.view')->only(['report']);
        $this->middleware('permission:attendance.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:attendance.export')->only(['export']);

        $this->attendance_service = $attendance_service;
        $this->employee_service = $employee_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'attendance';
    }

    public function index()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.attendance.index', compact('employees'));
    }

    public function getData(Request $request)
    {
        return $this->attendance_service->getData($request->all());
    }

    public function create()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.attendance.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $rules = [
            'employee_id' => 'required|exists:employees,employee_id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late,half_day,on_leave,holiday',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only([
            'attendance_id', 'employee_id', 'date', 'check_in_time', 'check_out_time', 'status', 'notes',
        ]);

        try {
            $this->attendance_service->save($obj);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'That employee already has an attendance record for this date.')->withInput();
        }

        return redirect('admin/attendance')
            ->with('success', empty($request->attendance_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($attendance_id)
    {
        $attendance = $this->attendance_service->getById($attendance_id);
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.attendance.create', compact('attendance', 'employees'));
    }

    public function destroy($attendance_id)
    {
        try {
            $this->attendance_service->delete($attendance_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function report(Request $request)
    {
        $employees = $this->employee_service->getAllActive();
        $month = $request->month ?? now()->month;
        $year = $request->year ?? now()->year;
        $summary = [];

        if ($request->employee_id) {
            $summary = $this->attendance_service->monthlySummary($request->employee_id, $year, $month);
        }

        return view('admin.hrm.attendance.report', compact('employees', 'summary', 'month', 'year'));
    }
}
