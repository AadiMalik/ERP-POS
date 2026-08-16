<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\Message;
use App\Enums\RoleNames;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WarehouseController extends Controller
{
    use ResponseAPI;

    protected $warehouse_service;
    protected $business_service;
    protected $branch_service;

    public function __construct(
        WarehouseService $warehouse_service,
        BusinessService $business_service,
        BranchService $branch_service
    ) {
        $this->warehouse_service = $warehouse_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAll();
        return view('admin.warehouse.index', compact('business', 'branches'));
    }

    public function getData(Request $request)
    {
        return $this->warehouse_service->getData($request->all());
    }
    public function create()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        return view('admin.warehouse.create', compact('business', 'branches'));
    }


    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('warehouses', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->warehouse_id, 'warehouse_id')
            ]
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


        $obj = $request->only([
            'warehouse_id',
            'name',
            'code',
            'phone',
            'address'
        ]);
        $obj['business_id'] = $request->business_id ??  Auth::user()->business_id;
        $obj['branch_id'] = $request->branch_id ??  Auth::user()->branch_id;
        $obj['status'] = $request->status ?? 'active';

        // create/update warehouse
        $warehouse = $this->warehouse_service->save($obj);
        return redirect('admin/warehouse')
            ->with('success', empty($request->warehouse_id) ? Message::SAVE : Message::UPDATE);
    }
    public function edit($warehouse_id)
    {
        $warehouse = $this->warehouse_service->getById($warehouse_id);
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        return view('admin.warehouse.create', compact('warehouse', 'business', 'branches'));
    }
    public function status($warehouse_id)
    {
        try {
            $this->warehouse_service->status($warehouse_id);
            return $this->success(
                Message::STATUS,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
    public function destroy($warehouse_id)
    {
        try {
            $this->warehouse_service->delete($warehouse_id);
            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $warehouses = $this->warehouse_service->getByBusiness($business_id);
            return $this->success(
                Message::SUCCESS,
                $warehouses
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byBranch($business_id)
    {
        try {
            $warehouses = $this->warehouse_service->getByBranch($business_id);
            return $this->success(
                Message::SUCCESS,
                $warehouses
            );
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
