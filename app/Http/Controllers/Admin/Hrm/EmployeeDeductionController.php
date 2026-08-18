<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\EmployeeDeductionService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeDeductionController extends Controller
{
    use ResponseAPI;

    protected $employee_deduction_service;
    protected $employee_service;

    public function __construct(EmployeeDeductionService $employee_deduction_service, EmployeeService $employee_service)
    {
        $this->middleware('permission:employee-deduction.view')->only(['index', 'getData']);
        $this->middleware('permission:employee-deduction.create')->only(['create']);
        $this->middleware('permission:employee-deduction.create|employee-deduction.edit')->only(['store']);
        $this->middleware('permission:employee-deduction.edit')->only(['edit']);
        $this->middleware('permission:employee-deduction.delete')->only(['destroy']);

        $this->employee_deduction_service = $employee_deduction_service;
        $this->employee_service = $employee_service;
    }

    public function index()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.employee-deduction.index', compact('employees'));
    }

    public function getData(Request $request)
    {
        return $this->employee_deduction_service->getData($request->all());
    }

    public function create()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.employee-deduction.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $rules = [
            'employee_id' => 'required|exists:employees,employee_id',
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'effective_from' => 'required|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['employee_deduction_id', 'employee_id', 'title', 'amount', 'effective_from', 'effective_to', 'status']);
        $obj['is_recurring'] = $request->has('is_recurring') ? 1 : 0;

        $this->employee_deduction_service->save($obj);

        return redirect('admin/employee-deduction')
            ->with('success', empty($request->employee_deduction_id) ? Message::SAVE : Message::UPDATE);
    }

    public function edit($employee_deduction_id)
    {
        $employee_deduction = $this->employee_deduction_service->getById($employee_deduction_id);
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.employee-deduction.create', compact('employee_deduction', 'employees'));
    }

    public function destroy($employee_deduction_id)
    {
        try {
            $this->employee_deduction_service->delete($employee_deduction_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
