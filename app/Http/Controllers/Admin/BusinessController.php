<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PackageService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $package_service;

    public function __construct(BusinessService $business_service, PackageService $package_service)
    {
        $this->business_service = $business_service;
        $this->package_service = $package_service;
    }

    public function index()
    {
        return view('admin.business.index');
    }

    public function getData(Request $request)
    {
        return $this->business_service->getData($request->all());
    }
    public function create()
    {
        $packages = $this->package_service->getAll();
        return view('admin.business.create', compact('packages'));
    }
    public function edit($id)
    {
        $business = $this->business_service->edit($id);
        $packages = $this->package_service->getAll();
        return view('admin.business.create', compact('business', 'packages'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|unique:businesses,name,' . $request->id,
            'price' => 'required|numeric',
            'duration_days' => 'required|integer',
            'duration_type' => 'required|string',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        try {

            $obj = $request->only([
                'id',
                'name',
                'description',
                'price',
                'order',
                'duration_type',
                'duration_days',
                'max_branches',
                'max_users',
                'max_customers',
                'max_warehouses',
                'max_categories',
                'max_products',
                'max_suppliers',
                'max_purchase_orders',
                'max_purchases',
                'max_sales',
                'max_transfers',
                'max_expenses',
                'max_vouchers'
            ]);

            $obj['status'] = $request->status ?? 1;

            $obj['is_pos_enabled'] =
                $request->is_pos_enabled ? 1 : 0;

            $obj['is_inventory_enabled'] =
                $request->is_inventory_enabled ? 1 : 0;

            $obj['is_accounting_enabled'] =
                $request->is_accounting_enabled ? 1 : 0;

            $obj['is_hrm_enabled'] =
                $request->is_hrm_enabled ? 1 : 0;

            $obj['is_payroll_enabled'] =
                $request->is_payroll_enabled ? 1 : 0;

            $package = $this->business_service->save($obj);

            return redirect('admin/business')->with('success', Message::SAVE);
        } catch (Exception $e) {

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {

            $this->business_service->delete($id);

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
}
