<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\EmployeeStatus;
use App\Enums\Message;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\DepartmentService;
use App\Services\Concrete\Admin\Hrm\DesignationService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Services\Concrete\Admin\Hrm\ShiftService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    use ResponseAPI;
    use HandlesImportExport;

    protected $employee_service;
    protected $department_service;
    protected $designation_service;
    protected $shift_service;

    public function __construct(
        EmployeeService $employee_service,
        DepartmentService $department_service,
        DesignationService $designation_service,
        ShiftService $shift_service
    ) {
        $this->middleware('permission:employee.view')->only(['index', 'getData']);
        $this->middleware('permission:employee.create')->only(['create']);
        $this->middleware('permission:employee.create|employee.edit')->only(['store']);
        $this->middleware('permission:employee.edit')->only(['edit']);
        $this->middleware('permission:employee.delete')->only(['destroy']);
        $this->middleware('permission:employee.status')->only(['status']);
        $this->middleware('permission:employee.document')->only(['storeDocument', 'destroyDocument']);
        $this->middleware('permission:employee.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:employee.export')->only(['export']);

        $this->employee_service = $employee_service;
        $this->department_service = $department_service;
        $this->designation_service = $designation_service;
        $this->shift_service = $shift_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'employee';
    }

    public function index()
    {
        $departments = $this->department_service->getAllActive();
        return view('admin.hrm.employee.index', compact('departments'));
    }

    public function getData(Request $request)
    {
        return $this->employee_service->getData($request->all());
    }

    public function create()
    {
        $departments = $this->department_service->getAllActive();
        $designations = $this->designation_service->getAllActive();
        $shifts = $this->shift_service->getAllActive();
        return view('admin.hrm.employee.create', compact('departments', 'designations', 'shifts'));
    }

    public function store(Request $request)
    {
        $employee_id = $request->employee_id;

        $rules = [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'joining_date' => 'nullable|date',
            'employment_type' => 'nullable|in:full_time,part_time,contract,intern',
            'payment_method' => 'nullable|in:bank,cash',
        ];

        if (empty($employee_id)) {
            $rules['email'] = 'required|email|unique:users,email';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only([
            'employee_id', 'name', 'email', 'phone', 'employee_code',
            'department_id', 'designation_id', 'shift_id', 'joining_date', 'employment_type',
            'dob', 'gender', 'marital_status', 'national_id', 'address',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation',
            'bank_name', 'bank_account_title', 'bank_account_number', 'bank_branch_code', 'payment_method',
        ]);

        try {
            $result = $this->employee_service->save($obj);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        $redirect = redirect('admin/employee')
            ->with('success', empty($employee_id) ? Message::SAVE : Message::UPDATE);

        if (!empty($result['password'])) {
            $redirect->with('generated_email', $result['employee']->user->email)
                ->with('generated_password', $result['password']);
        }

        return $redirect;
    }

    public function edit($employee_id)
    {
        $employee = $this->employee_service->getById($employee_id);
        $departments = $this->department_service->getAllActive();
        $designations = $this->designation_service->getAllActive();
        $shifts = $this->shift_service->getAllActive();
        $statuses = EmployeeStatus::manuallySettable();
        return view('admin.hrm.employee.create', compact('employee', 'departments', 'designations', 'shifts', 'statuses'));
    }

    public function status(Request $request, $employee_id)
    {
        try {
            $this->employee_service->changeStatus($employee_id, $request->status);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($employee_id)
    {
        try {
            $this->employee_service->delete($employee_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function storeDocument(Request $request, $employee_id)
    {
        $rules = [
            'document_type' => 'required|string|max:100',
            'file' => 'required|file|max:5120',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate);
        }

        $this->employee_service->uploadDocument(
            $employee_id,
            $request->document_type,
            $request->file('file'),
            $request->expiry_date,
            $request->notes
        );

        return redirect()->route('employee.edit', $employee_id)->with('success', Message::SAVE);
    }

    public function destroyDocument($employee_document_id)
    {
        try {
            $this->employee_service->deleteDocument($employee_document_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
