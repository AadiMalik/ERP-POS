<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\FixedAssetCategoryService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FixedAssetCategoryController extends Controller
{
    use ResponseAPI;

    protected $category_service;
    protected $business_service;
    protected $branch_service;

    public function __construct(
        FixedAssetCategoryService $category_service,
        BusinessService $business_service,
        BranchService $branch_service
    ) {
        $this->middleware('permission:fixed-asset-category.view')->only(['index', 'getData']);
        $this->middleware('permission:fixed-asset-category.create')->only(['create']);
        $this->middleware('permission:fixed-asset-category.create|fixed-asset-category.edit')->only(['store']);
        $this->middleware('permission:fixed-asset-category.edit')->only(['edit']);
        $this->middleware('permission:fixed-asset-category.delete')->only(['destroy']);
        $this->middleware('permission:fixed-asset-category.status')->only(['status']);
        $this->middleware('module:fixed-asset-category');

        $this->category_service = $category_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        return view('admin.fixed_asset_category.index', compact('business'));
    }

    public function getData(Request $request)
    {
        $filters = $request->all();
        if (getRoleName() !== RoleNames::SUPERADMIN) {
            $filters['business_id'] = Auth::user()->business_id;
        }
        return $this->category_service->getData($filters);
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        return view('admin.fixed_asset_category.create', compact('business'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fixed_asset_categories', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->fixed_asset_category_id, 'fixed_asset_category_id'),
            ],
            'default_useful_life_years' => 'required|integer|min:1|max:100',
            'default_residual_percent' => 'nullable|numeric|min:0|max:100',
        ];
        if (getRoleName() == RoleNames::SUPERADMIN) {
            $rules['business_id'] = 'required|exists:businesses,business_id';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only([
            'fixed_asset_category_id',
            'name',
            'code',
            'description',
            'default_useful_life_years',
            'default_residual_percent',
            'status',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['default_depreciation_method'] = 'straight_line';

        try {
            $this->category_service->save($obj);
            return redirect('admin/fixed-asset-category')
                ->with('success', empty($request->fixed_asset_category_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit($fixed_asset_category_id)
    {
        $category = $this->category_service->getById($fixed_asset_category_id);
        if (!$category) {
            return redirect('admin/fixed-asset-category')->with('error', Message::NOTFOUND);
        }
        $business = $this->business_service->getAll();
        return view('admin.fixed_asset_category.create', compact('category', 'business'));
    }

    public function status($fixed_asset_category_id)
    {
        try {
            $this->category_service->changeStatus($fixed_asset_category_id);
            return $this->success(Message::UPDATE);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($fixed_asset_category_id)
    {
        try {
            $this->category_service->delete($fixed_asset_category_id);
            return $this->success(Message::DELETE);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
