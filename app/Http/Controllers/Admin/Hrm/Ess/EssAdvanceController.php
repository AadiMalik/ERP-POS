<?php

namespace App\Http\Controllers\Admin\Hrm\Ess;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\EmployeeAdvanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EssAdvanceController extends Controller
{
    protected $employee_advance_service;

    public function __construct(EmployeeAdvanceService $employee_advance_service)
    {
        $this->middleware('permission:ess.advance.apply');

        $this->employee_advance_service = $employee_advance_service;
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
        $advances = \App\Models\EmployeeAdvance::where('employee_id', $employee->employee_id)
            ->where('is_deleted', 0)
            ->orderBy('date_created', 'desc')
            ->get();

        return view('admin.hrm.ess.advance.index', compact('advances'));
    }

    public function create()
    {
        return view('admin.hrm.ess.advance.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'amount' => 'required|numeric|min:1',
            'installments_count' => 'required|integer|min:1',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['amount', 'reason', 'installments_count']);
        $obj['employee_id'] = $this->employee()->employee_id;

        $this->employee_advance_service->request($obj);

        return redirect()->route('ess.advance.index')->with('success', 'Advance request submitted.');
    }
}
