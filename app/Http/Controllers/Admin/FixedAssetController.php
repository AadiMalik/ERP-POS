<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DepreciationAdjustmentModes;
use App\Enums\DepreciationFrequencies;
use App\Enums\FixedAssetDisposalTypes;
use App\Enums\FixedAssetStatuses;
use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\FixedAssetCategoryService;
use App\Services\Concrete\Admin\FixedAssetService;
use App\Services\Concrete\Admin\SupplierService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FixedAssetController extends Controller
{
    use ResponseAPI;

    protected $fixed_asset_service;
    protected $category_service;
    protected $business_service;
    protected $branch_service;
    protected $supplier_service;
    protected $account_service;

    public function __construct(
        FixedAssetService $fixed_asset_service,
        FixedAssetCategoryService $category_service,
        BusinessService $business_service,
        BranchService $branch_service,
        SupplierService $supplier_service,
        AccountService $account_service
    ) {
        $this->middleware('permission:fixed-asset.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:fixed-asset.create')->only(['create']);
        $this->middleware('permission:fixed-asset.create|fixed-asset.edit')->only(['store']);
        $this->middleware('permission:fixed-asset.edit')->only(['edit']);
        $this->middleware('permission:fixed-asset.delete')->only(['destroy']);
        $this->middleware('permission:fixed-asset.depreciate')->only(['depreciate']);
        $this->middleware('permission:fixed-asset.pause')->only(['pause']);
        $this->middleware('permission:fixed-asset.resume')->only(['resume']);
        $this->middleware('permission:fixed-asset.dispose')->only(['dispose']);
        $this->middleware('permission:fixed-asset.adjust')->only(['adjust']);
        $this->middleware('permission:fixed-asset.transfer')->only(['transfer']);
        $this->middleware('module:fixed-asset');

        $this->fixed_asset_service = $fixed_asset_service;
        $this->category_service = $category_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->supplier_service = $supplier_service;
        $this->account_service = $account_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAll();
        $categories = $this->category_service->getAllActive(Auth::user()->business_id);
        $statuses = FixedAssetStatuses::labels();
        return view('admin.fixed_asset.index', compact('business', 'branches', 'categories', 'statuses'));
    }

    public function getData(Request $request)
    {
        $filters = $request->all();
        if (getRoleName() !== RoleNames::SUPERADMIN) {
            $filters['business_id'] = Auth::user()->business_id;
        }
        return $this->fixed_asset_service->getData($filters);
    }

    public function create()
    {
        return $this->formView();
    }

    public function edit($fixed_asset_id)
    {
        $fixed_asset = $this->fixed_asset_service->getById($fixed_asset_id);
        if (!$fixed_asset) {
            return redirect('admin/fixed-asset')->with('error', Message::NOTFOUND);
        }
        return $this->formView($fixed_asset);
    }

    public function show($fixed_asset_id)
    {
        $fixed_asset = $this->fixed_asset_service->getById($fixed_asset_id);
        if (!$fixed_asset) {
            return redirect('admin/fixed-asset')->with('error', Message::NOTFOUND);
        }
        $disposal_types = FixedAssetDisposalTypes::labels();
        $frequencies = DepreciationFrequencies::labels();
        $adjustment_modes = DepreciationAdjustmentModes::labels();
        $accounts = $this->account_service->getAllActive($fixed_asset->business_id);
        $branches = $this->branch_service->getAllActive();
        return view('admin.fixed_asset.show', compact(
            'fixed_asset',
            'disposal_types',
            'frequencies',
            'adjustment_modes',
            'accounts',
            'branches'
        ));
    }

    protected function formView($fixed_asset = null)
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $categories = $this->category_service->getAllActive(Auth::user()->business_id ?? ($fixed_asset->business_id ?? null));
        $suppliers = $this->supplier_service->getAllActive();
        $accounts = $this->account_service->getAllActive(Auth::user()->business_id ?? ($fixed_asset->business_id ?? null));
        $frequencies = DepreciationFrequencies::labels();
        $adjustment_modes = DepreciationAdjustmentModes::labels();
        return view('admin.fixed_asset.create', compact(
            'fixed_asset',
            'business',
            'branches',
            'categories',
            'suppliers',
            'accounts',
            'frequencies',
            'adjustment_modes'
        ));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'useful_life_years' => 'required|integer|min:1|max:100',
            'residual_value' => 'nullable|numeric|min:0',
            'residual_percent' => 'nullable|numeric|min:0|max:100',
            'min_book_value_percent' => 'nullable|numeric|min:0|max:100',
            'depreciation_frequency' => ['required', Rule::in(DepreciationFrequencies::all())],
            'depreciation_adjustment_mode' => ['nullable', Rule::in(DepreciationAdjustmentModes::all())],
            'depreciation_adjustment_rate' => 'nullable|numeric|min:0|max:100',
            'fixed_asset_category_id' => 'nullable|exists:fixed_asset_categories,fixed_asset_category_id',
            'supplier_id' => 'nullable|exists:suppliers,supplier_id',
            'purchase_id' => 'nullable|exists:purchases,purchase_id',
            'payment_account_id' => 'nullable|exists:accounts,account_id',
        ];
        if (getRoleName() == RoleNames::SUPERADMIN) {
            $rules['business_id'] = 'required|exists:businesses,business_id';
        }
        if (in_array(getRoleName(), RoleNames::branchLevelRoles())) {
            $rules['branch_id'] = 'required|exists:branches,branch_id';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        if ((float) $request->purchase_cost < 0) {
            return redirect()->back()->with('error', 'Purchase cost cannot be negative.')->withInput();
        }
        if ($request->residual_value !== null && (float) $request->residual_value > (float) $request->purchase_cost) {
            return redirect()->back()->with('error', 'Residual value cannot exceed purchase cost.')->withInput();
        }

        $obj = $request->only([
            'fixed_asset_id',
            'fixed_asset_category_id',
            'asset_code',
            'name',
            'description',
            'serial_number',
            'location',
            'purchase_date',
            'purchase_cost',
            'residual_value',
            'residual_percent',
            'min_book_value_percent',
            'useful_life_years',
            'depreciation_frequency',
            'depreciation_adjustment_mode',
            'depreciation_adjustment_rate',
            'supplier_id',
            'purchase_id',
            'payment_account_id',
            'accounting_from_purchase',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id;
        $obj['depreciation_method'] = 'straight_line';
        $obj['accounting_from_purchase'] = $request->boolean('accounting_from_purchase');

        try {
            $this->fixed_asset_service->save($obj);
            return redirect('admin/fixed-asset')
                ->with('success', empty($request->fixed_asset_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy($fixed_asset_id)
    {
        try {
            $this->fixed_asset_service->delete($fixed_asset_id);
            return $this->success(Message::DELETE);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function pause(Request $request, $fixed_asset_id)
    {
        try {
            $this->fixed_asset_service->pause($fixed_asset_id, $request->reason);
            return $this->success(Message::UPDATE);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function resume(Request $request, $fixed_asset_id)
    {
        try {
            $this->fixed_asset_service->resume($fixed_asset_id, $request->reason);
            return $this->success(Message::UPDATE);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function depreciate($fixed_asset_id)
    {
        try {
            $dep = $this->fixed_asset_service->depreciateNow($fixed_asset_id);
            return $this->success('Depreciation posted successfully.', $dep);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function transfer(Request $request, $fixed_asset_id)
    {
        $validate = Validator::make($request->all(), [
            'branch_id' => 'nullable|exists:branches,branch_id',
            'location' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->fixed_asset_service->transfer($fixed_asset_id, $request->all());
            return $this->success(Message::UPDATE);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function adjust(Request $request, $fixed_asset_id)
    {
        $validate = Validator::make($request->all(), [
            'depreciation_adjustment_mode' => ['nullable', Rule::in(DepreciationAdjustmentModes::all())],
            'depreciation_adjustment_rate' => 'nullable|numeric|min:0|max:100',
            'min_book_value_percent' => 'nullable|numeric|min:0|max:100',
            'residual_value' => 'nullable|numeric|min:0',
            'depreciation_frequency' => ['nullable', Rule::in(DepreciationFrequencies::all())],
            'useful_life_years' => 'nullable|integer|min:1|max:100',
            'remarks' => 'nullable|string|max:500',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->fixed_asset_service->adjust($fixed_asset_id, $request->all());
            return $this->success(Message::UPDATE);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function dispose(Request $request, $fixed_asset_id)
    {
        $validate = Validator::make($request->all(), [
            'disposal_type' => ['required', Rule::in(FixedAssetDisposalTypes::all())],
            'disposal_date' => 'required|date',
            'sale_price' => 'nullable|numeric|min:0',
            'disposal_reason' => 'nullable|string|max:500',
            'disposal_proceeds_account_id' => 'nullable|exists:accounts,account_id',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        if (FixedAssetDisposalTypes::requiresSalePrice($request->disposal_type)
            && ($request->sale_price === null || $request->sale_price === '')) {
            return $this->error('Sale price is required for sale disposals.');
        }
        try {
            $asset = $this->fixed_asset_service->dispose($fixed_asset_id, $request->all());
            return $this->success(Message::UPDATE, $asset);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
