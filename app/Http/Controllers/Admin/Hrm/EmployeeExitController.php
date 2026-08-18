<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\EmployeeExitService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeExitController extends Controller
{
    use ResponseAPI;

    protected $employee_exit_service;
    protected $employee_service;

    public function __construct(EmployeeExitService $employee_exit_service, EmployeeService $employee_service)
    {
        $this->middleware('permission:employee-exit.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:employee-exit.create')->only(['create', 'store']);
        $this->middleware('permission:employee-exit.approve')->only(['decide']);
        $this->middleware('permission:employee-exit.finalize')->only(['finalize']);
        $this->middleware('permission:employee-clearance.manage')->only(['clear']);

        $this->employee_exit_service = $employee_exit_service;
        $this->employee_service = $employee_service;
    }

    public function index()
    {
        return view('admin.hrm.employee-exit.index');
    }

    public function getData(Request $request)
    {
        return $this->employee_exit_service->getData($request->all());
    }

    public function create()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.employee-exit.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $rules = [
            'employee_id' => 'required|exists:employees,employee_id',
            'type' => 'required|in:resignation,termination',
            'notice_period_days' => 'nullable|integer|min:0',
            'last_working_date' => 'nullable|date',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $exit = $this->employee_exit_service->request($request->only(['employee_id', 'type', 'notice_period_days', 'last_working_date', 'reason']));

        return redirect()->route('employee-exit.show', $exit->employee_exit_id)->with('success', Message::SAVE);
    }

    public function show($employee_exit_id)
    {
        $exit = $this->employee_exit_service->getById($employee_exit_id);
        return view('admin.hrm.employee-exit.show', compact('exit'));
    }

    public function decide(Request $request, $employee_exit_id)
    {
        try {
            $this->employee_exit_service->decide($employee_exit_id, $request->status);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function finalize(Request $request, $employee_exit_id)
    {
        try {
            $this->employee_exit_service->finalize($employee_exit_id, $request->final_settlement_amount);
            return $this->success('Exit finalized.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function clear(Request $request, $exit_clearance_id)
    {
        try {
            $this->employee_exit_service->clear($exit_clearance_id, $request->status, $request->remarks);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
