<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\PackageService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    use ResponseAPI;

    protected $package_service;

    public function __construct(PackageService $package_service)
    {
        $this->package_service = $package_service;
    }

    public function index()
    {
        return view('admin.packages.index');
    }

    public function getData(Request $request)
    {
        return $this->package_service->getData($request->all());
    }
    public function create()
    {
        return view('admin.packages.create');
    }
    public function edit($id)
    {
        $package = $this->package_service->getById($id);
        return view('admin.packages.create', compact('package'));
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('packages')
                    ->where(function ($query) use ($request) {
                        return $query->where(
                            'duration_type',
                            $request->duration_type
                        )->where('is_deleted', 0);
                    })
                    ->ignore($request->id)
            ],
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

            $package = $this->package_service->save($obj);

            return redirect('admin/packages')->with('success', Message::SAVE);
        } catch (Exception $e) {

            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }
    public function show($id)
    {
        try {
            $package = $this->package_service->getById($id);
            return $this->success(
                Message::FETCH,
                $package
            );
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
    public function destroy($id)
    {
        try {

            $this->package_service->delete($id);

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
