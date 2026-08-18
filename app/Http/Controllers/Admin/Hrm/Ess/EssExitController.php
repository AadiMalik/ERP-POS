<?php

namespace App\Http\Controllers\Admin\Hrm\Ess;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\EmployeeExitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EssExitController extends Controller
{
    protected $employee_exit_service;

    public function __construct(EmployeeExitService $employee_exit_service)
    {
        $this->middleware('permission:ess.resignation.apply');

        $this->employee_exit_service = $employee_exit_service;
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
        $exits = \App\Models\EmployeeExit::where('employee_id', $employee->employee_id)
            ->where('is_deleted', 0)
            ->orderBy('date_created', 'desc')
            ->get();

        return view('admin.hrm.ess.exit.index', compact('exits'));
    }

    public function create()
    {
        return view('admin.hrm.ess.exit.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'notice_period_days' => 'nullable|integer|min:0',
            'last_working_date' => 'nullable|date',
            'reason' => 'nullable|string',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['notice_period_days', 'last_working_date', 'reason']);
        $obj['employee_id'] = $this->employee()->employee_id;
        $obj['type'] = 'resignation';

        $this->employee_exit_service->request($obj);

        return redirect()->route('ess.exit.index')->with('success', 'Resignation submitted.');
    }
}
