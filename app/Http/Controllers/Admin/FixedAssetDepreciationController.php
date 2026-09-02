<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FixedAssetStatuses;
use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\FixedAsset;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\FixedAssetDepreciationService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FixedAssetDepreciationController extends Controller
{
    use ResponseAPI;

    protected $depreciation_service;
    protected $business_service;
    protected $branch_service;

    public function __construct(
        FixedAssetDepreciationService $depreciation_service,
        BusinessService $business_service,
        BranchService $branch_service
    ) {
        $this->middleware('permission:fixed-asset-depreciation.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:fixed-asset-depreciation.create')->only(['create', 'store']);
        $this->middleware('permission:fixed-asset-depreciation.delete')->only(['destroy']);
        $this->middleware('module:fixed-asset-depreciation');

        $this->depreciation_service = $depreciation_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAll();
        $assets = $this->activeAssetsForBusiness(Auth::user()->business_id);

        return view('admin.fixed_asset_depreciation.index', compact('business', 'branches', 'assets'));
    }

    public function getData(Request $request)
    {
        $filters = $request->all();
        if (getRoleName() !== RoleNames::SUPERADMIN) {
            $filters['business_id'] = Auth::user()->business_id;
        }

        return $this->depreciation_service->getData($filters);
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $assets = $this->activeAssetsForBusiness(Auth::user()->business_id);

        return view('admin.fixed_asset_depreciation.create', compact('business', 'assets'));
    }

    public function store(Request $request)
    {
        $rules = [
            'fixed_asset_id' => 'required|exists:fixed_assets,fixed_asset_id',
            'depreciation_date' => 'nullable|date',
        ];
        if (getRoleName() === RoleNames::SUPERADMIN) {
            $rules['business_id'] = 'required|exists:businesses,business_id';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only(['fixed_asset_id', 'depreciation_date']);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        try {
            $dep = $this->depreciation_service->create($obj);
            return redirect('admin/fixed-asset-depreciation/show/' . $dep->fixed_asset_depreciation_id)
                ->with('success', Message::SAVE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show($fixed_asset_depreciation_id)
    {
        $depreciation = $this->depreciation_service->getById($fixed_asset_depreciation_id);
        if (!$depreciation) {
            return redirect('admin/fixed-asset-depreciation')->with('error', Message::NOTFOUND);
        }

        if (getRoleName() !== RoleNames::SUPERADMIN
            && $depreciation->business_id !== Auth::user()->business_id) {
            return redirect('admin/fixed-asset-depreciation')->with('error', Message::NOTFOUND);
        }

        return view('admin.fixed_asset_depreciation.show', compact('depreciation'));
    }

    public function destroy($fixed_asset_depreciation_id)
    {
        try {
            $dep = $this->depreciation_service->getById($fixed_asset_depreciation_id);
            if (!$dep) {
                return $this->error(Message::NOTFOUND);
            }
            if (getRoleName() !== RoleNames::SUPERADMIN
                && $dep->business_id !== Auth::user()->business_id) {
                return $this->error(Message::NOTFOUND);
            }

            $this->depreciation_service->reverse($fixed_asset_depreciation_id);
            return $this->success(Message::DELETE);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function activeAssetsForBusiness(?string $businessId)
    {
        $q = FixedAsset::where('is_deleted', 0)
            ->where('depreciation_status', FixedAssetStatuses::ACTIVE)
            ->orderBy('asset_code');

        if ($businessId) {
            $q->where('business_id', $businessId);
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::FINANCEMANAGER,
            RoleNames::ACCOUNTANT,
            RoleNames::BRANCHADMIN,
        ];

        return applyRoleScope($q, $allow_roles)->get();
    }
}
