<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\Hrm\LeaveRequestService;
use App\Services\Concrete\Admin\Hrm\LeaveTypeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends Controller
{
    use ResponseAPI;

    protected $leave_request_service;
    protected $employee_service;
    protected $leave_type_service;

    public function __construct(LeaveRequestService $leave_request_service, EmployeeService $employee_service, LeaveTypeService $leave_type_service)
    {
        $this->middleware('permission:leave-request.view')->only(['index', 'getData']);
        $this->middleware('permission:leave-request.create')->only(['create']);
        $this->middleware('permission:leave-request.create|leave-request.edit')->only(['store']);
        $this->middleware('permission:leave-request.approve')->only(['decide']);
        $this->middleware('permission:leave-request.delete')->only(['destroy']);

        $this->leave_request_service = $leave_request_service;
        $this->employee_service = $employee_service;
        $this->leave_type_service = $leave_type_service;
    }

    public function index()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.leave-request.index', compact('employees'));
    }

    public function getData(Request $request)
    {
        return $this->leave_request_service->getData($request->all());
    }

    public function create()
    {
        $employees = $this->employee_service->getAllActive();
        $leave_types = $this->leave_type_service->getAllActive();
        return view('admin.hrm.leave-request.create', compact('employees', 'leave_types'));
    }

    public function store(Request $request)
    {
        $rules = [
            'employee_id' => 'required|exists:employees,employee_id',
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

        $obj = $request->only(['employee_id', 'leave_type_id', 'start_date', 'end_date', 'reason']);
        $obj['attachment'] = $request->file('attachment');

        try {
            $this->leave_request_service->save($obj);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect('admin/leave-request')->with('success', Message::SAVE);
    }

    public function decide(Request $request, $leave_request_id)
    {
        try {
            $this->leave_request_service->decide($leave_request_id, $request->status, $request->remarks);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($leave_request_id)
    {
        try {
            $this->leave_request_service->delete($leave_request_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
