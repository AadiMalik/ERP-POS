<?php

namespace App\Http\Controllers\Admin\Hrm\Ess;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Hrm\AssetAllocationService;
use Illuminate\Support\Facades\Auth;

class EssProfileController extends Controller
{
    protected $asset_allocation_service;

    public function __construct(AssetAllocationService $asset_allocation_service)
    {
        $this->middleware('permission:ess.profile.view');

        $this->asset_allocation_service = $asset_allocation_service;
    }

    public function index()
    {
        $employee = Auth::user()->employee;
        if (!$employee) {
            abort(403, 'No employee profile is linked to your account. Please contact HR.');
        }

        $employee->load(['department', 'designation', 'shift', 'documents']);
        $allocated_assets = $this->asset_allocation_service->getByEmployee($employee->employee_id);

        return view('admin.hrm.ess.profile', compact('employee', 'allocated_assets'));
    }
}
