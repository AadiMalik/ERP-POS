<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\PayrollService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    use ResponseAPI;

    protected $payroll_service;

    public function __construct(PayrollService $payroll_service)
    {
        $this->middleware('permission:payroll.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:payroll.create')->only(['create', 'store']);
        $this->middleware('permission:payroll.finalize')->only(['finalize']);
        $this->middleware('permission:payroll.pay')->only(['pay']);
        $this->middleware('permission:payroll.reopen')->only(['reopen']);

        $this->payroll_service = $payroll_service;
    }

    public function index()
    {
        return view('admin.hrm.payroll.index');
    }

    public function getData(Request $request)
    {
        return $this->payroll_service->getData($request->all());
    }

    public function create()
    {
        return view('admin.hrm.payroll.create');
    }

    public function store(Request $request)
    {
        $rules = [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        try {
            $run = $this->payroll_service->generate($request->month, $request->year, $request->branch_id);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('payroll.show', $run->payroll_run_id)->with('success', 'Payroll generated successfully.');
    }

    public function show($payroll_run_id)
    {
        $run = $this->payroll_service->getById($payroll_run_id);
        return view('admin.hrm.payroll.show', compact('run'));
    }

    public function finalize($payroll_run_id)
    {
        try {
            $this->payroll_service->finalize($payroll_run_id);
            return $this->success('Payroll finalized.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function pay($payroll_run_id)
    {
        try {
            $this->payroll_service->pay($payroll_run_id);
            return $this->success('Payroll marked as paid.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reopen($payroll_run_id)
    {
        try {
            $this->payroll_service->reopen($payroll_run_id);
            return $this->success('Payroll reopened.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
