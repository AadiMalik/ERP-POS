<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\EmployeeLedgerService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use Illuminate\Http\Request;

class EmployeeLedgerController extends Controller
{
    protected $employee_ledger_service;
    protected $employee_service;

    public function __construct(EmployeeLedgerService $employee_ledger_service, EmployeeService $employee_service)
    {
        $this->middleware('permission:employee-ledger.view');

        $this->employee_ledger_service = $employee_ledger_service;
        $this->employee_service = $employee_service;
    }

    public function index()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.employee-ledger.index', compact('employees'));
    }

    public function getData(Request $request)
    {
        return $this->employee_ledger_service->getData($request->all());
    }
}
