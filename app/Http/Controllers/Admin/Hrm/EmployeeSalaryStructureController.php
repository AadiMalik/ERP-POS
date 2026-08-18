<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Services\Concrete\Admin\Hrm\EmployeeSalaryStructureService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\Hrm\SalaryComponentService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeSalaryStructureController extends Controller
{
    use ResponseAPI;

    protected $salary_structure_service;
    protected $employee_service;
    protected $salary_component_service;

    public function __construct(EmployeeSalaryStructureService $salary_structure_service, EmployeeService $employee_service, SalaryComponentService $salary_component_service)
    {
        $this->middleware('permission:salary-structure.view')->only(['index', 'manage']);
        $this->middleware('permission:salary-structure.create|salary-structure.edit')->only(['store']);
        $this->middleware('permission:salary-structure.delete')->only(['destroy']);

        $this->salary_structure_service = $salary_structure_service;
        $this->employee_service = $employee_service;
        $this->salary_component_service = $salary_component_service;
    }

    public function index()
    {
        $employees = Employee::with(['user', 'activeSalaryStructure'])
            ->where('is_deleted', 0)
            ->get();
        return view('admin.hrm.salary-structure.index', compact('employees'));
    }

    public function manage($employee_id)
    {
        $employee = Employee::with('user')->findOrFail($employee_id);
        $current = $this->salary_structure_service->getCurrent($employee_id);
        $history = $this->salary_structure_service->getHistory($employee_id);
        $earning_components = $this->salary_component_service->getAllActive('earning');
        $deduction_components = $this->salary_component_service->getAllActive('deduction');

        return view('admin.hrm.salary-structure.manage', compact('employee', 'current', 'history', 'earning_components', 'deduction_components'));
    }

    public function store(Request $request, $employee_id)
    {
        $rules = [
            'effective_from' => 'required|date',
            'basic_salary' => 'required|numeric|min:0',
            'overtime_rate_per_hour' => 'nullable|numeric|min:0',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['effective_from', 'basic_salary', 'overtime_rate_per_hour', 'salary_component_id', 'amount_or_percentage']);
        $obj['employee_id'] = $employee_id;

        $this->salary_structure_service->save($obj);

        return redirect()->route('salary-structure.manage', $employee_id)->with('success', Message::SAVE);
    }

    public function destroy($employee_salary_structure_id)
    {
        try {
            $structure = EmployeeSalaryStructure::findOrFail($employee_salary_structure_id);
            if ($structure->status == 'active') {
                throw new Exception('The active salary structure cannot be deleted. Assign a new version instead.');
            }
            $structure->update(['is_deleted' => 1]);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
