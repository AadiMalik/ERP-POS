<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\AssetAllocationService;
use App\Services\Concrete\Admin\Hrm\AssetService;
use App\Services\Concrete\Admin\Hrm\EmployeeService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AssetAllocationController extends Controller
{
    use ResponseAPI;

    protected $asset_allocation_service;
    protected $asset_service;
    protected $employee_service;

    public function __construct(AssetAllocationService $asset_allocation_service, AssetService $asset_service, EmployeeService $employee_service)
    {
        $this->middleware('permission:asset-allocation.view')->only(['index', 'getData']);
        $this->middleware('permission:asset-allocation.create')->only(['create', 'store']);
        $this->middleware('permission:asset-allocation.edit')->only(['returnAsset']);

        $this->asset_allocation_service = $asset_allocation_service;
        $this->asset_service = $asset_service;
        $this->employee_service = $employee_service;
    }

    public function index()
    {
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.asset-allocation.index', compact('employees'));
    }

    public function getData(Request $request)
    {
        return $this->asset_allocation_service->getData($request->all());
    }

    public function create()
    {
        $assets = $this->asset_service->getAvailable();
        $employees = $this->employee_service->getAllActive();
        return view('admin.hrm.asset-allocation.create', compact('assets', 'employees'));
    }

    public function store(Request $request)
    {
        $rules = [
            'asset_id' => 'required|exists:assets,asset_id',
            'employee_id' => 'required|exists:employees,employee_id',
            'issue_date' => 'required|date',
            'expected_return_date' => 'nullable|date|after_or_equal:issue_date',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        try {
            $this->asset_allocation_service->issue($request->only(['asset_id', 'employee_id', 'issue_date', 'expected_return_date', 'condition_on_issue', 'remarks']));
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }

        return redirect('admin/asset-allocation')->with('success', 'Asset issued successfully.');
    }

    public function returnAsset(Request $request, $asset_allocation_id)
    {
        try {
            $this->asset_allocation_service->returnAsset($asset_allocation_id, $request->condition_on_return, $request->remarks);
            return $this->success('Asset returned.', []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
