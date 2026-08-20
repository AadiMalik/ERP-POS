<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\EmployeeAdvanceService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmployeeAdvanceController extends Controller
{
    use ResponseAPI;
    use HandlesImportExport;

    protected $employee_advance_service;
    protected $employee_service;

    public function __construct(EmployeeAdvanceService $employee_advance_service, EmployeeService $employee_service)
    {
        $this->middleware('permission:employee-advance.view')->only(['index', 'getData']);
        $this->middleware('permission:employee-advance.create')->only(['create']);
        $this->middleware('permission:employee-advance.create|employee-advance.edit')->only(['store']);
        $this->middleware('permission:employee-advance.approve')->only(['decide']);
        $this->middleware('permission:employee-advance.delete')->only(['destroy']);
        $this->middleware('permission:employee-advance.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:employee-advance.export')->only(['export']);

        $this->employee_advance_service = $employee_advance_service;
        $this->employee_service = $employee_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'employee-advance';
    }

    public function index()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.employee-advance.index', compact('employees'));
    }

    public function getData(Request $request)
    {
        return $this->employee_advance_service->getData($request->all());
    }

    public function create()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.employee-advance.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $rules = [
            'employee_id' => 'required|exists:employees,employee_id',
            'amount' => 'required|numeric|min:1',
            'installments_count' => 'required|integer|min:1',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $this->employee_advance_service->request($request->only(['employee_id', 'amount', 'reason', 'installments_count']));

        return redirect('admin/employee-advance')->with('success', Message::SAVE);
    }

    public function decide(Request $request, $employee_advance_id)
    {
        try {
            $this->employee_advance_service->decide($employee_advance_id, $request->status, $request->installments_count ?? 1);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($employee_advance_id)
    {
        try {
            $this->employee_advance_service->delete($employee_advance_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
