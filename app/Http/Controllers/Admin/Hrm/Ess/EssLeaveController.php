<?php

namespace App\Http\Controllers\Admin\Hrm\Ess;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\LeaveRequestService;
use App\Services\Concrete\Admin\Hrm\LeaveTypeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EssLeaveController extends Controller
{
    use ResponseAPI;

    protected $leave_request_service;
    protected $leave_type_service;

    public function __construct(LeaveRequestService $leave_request_service, LeaveTypeService $leave_type_service)
    {
        $this->middleware('permission:ess.leave.view')->only(['index']);
        $this->middleware('permission:ess.leave.apply')->only(['create', 'store', 'cancel']);

        $this->leave_request_service = $leave_request_service;
        $this->leave_type_service = $leave_type_service;
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
        $leave_requests = $this->leave_request_service->getByEmployee($employee->employee_id);
        $leave_types = $this->leave_type_service->getAllActive();

        $balances = $leave_types->map(function ($type) use ($employee) {
            return array_merge(['leave_type' => $type->name], $this->leave_request_service->getBalance($employee->employee_id, $type->leave_type_id));
        });

        return view('admin.hrm.ess.leave.index', compact('leave_requests', 'balances'));
    }

    public function create()
    {
        $leave_types = $this->leave_type_service->getAllActive();
        return view('admin.hrm.ess.leave.create', compact('leave_types'));
    }

    public function store(Request $request)
    {
        $rules = [
            'leave_type_id' => 'required|exists:leave_types,leave_type_id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['leave_type_id', 'start_date', 'end_date', 'reason']);
        $obj['employee_id'] = $this->employee()->employee_id;
        $obj['attachment'] = $request->file('attachment');

        try {
            $this->leave_request_service->save($obj);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('ess.leave.index')->with('success', 'Leave request submitted.');
    }

    public function cancel($leave_request_id)
    {
        $leave_request = $this->leave_request_service->getById($leave_request_id);
        if ($leave_request->employee_id != $this->employee()->employee_id) {
            abort(403);
        }

        try {
            $this->leave_request_service->cancel($leave_request_id);
            return $this->success('Leave request cancelled.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
