<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\PurchaseOrderService;
use App\Services\Concrete\Admin\SupplierService;
use App\Services\Concrete\Admin\UnitService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    use ResponseAPI;

    protected $purchase_order_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $supplier_service;
    protected $unit_service;

    public function __construct(
        PurchaseOrderService $purchase_order_service,
        ProductService $product_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        WarehouseService $warehouse_service,
        UnitService $unit_service
    ) {
        $this->purchase_order_service = $purchase_order_service;
        $this->product_service = $product_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->warehouse_service = $warehouse_service;
        $this->unit_service = $unit_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::COMPLETED => ucfirst(Status::COMPLETED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.purchase_order.index', compact('business', 'suppliers', 'warehouses', 'statuses'));
    }
    public function getData(Request $request)
    {
        return $this->purchase_order_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        $purchase_order_no = generatePONo();

        return view('admin.purchase_order.create', compact('business', 'products', 'suppliers', 'warehouses', 'units', 'purchase_order_no'));
    }

    public function edit($purchase_order_id)
    {
        $purchase_order = $this->purchase_order_service->getById($purchase_order_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();

        return view('admin.purchase_order.create', compact('purchase_order', 'business', 'products', 'suppliers', 'warehouses', 'units'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'purchase_order_date' => utcDate($request->purchase_order_date),
            'purchase_expected_date' => utcDate($request->purchase_expected_date),
        ]);
        $validator = Validator::make($request->all(), [
            'supplier_id' => ['required', Rule::exists('suppliers', 'supplier_id')->where('is_deleted', 0)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'purchase_order_no' => [
                'required',
                Rule::unique('purchase_orders', 'purchase_order_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->purchase_order_id, 'purchase_order_id')
            ],
            'purchase_order_date' => ['required', 'date'],
            'purchase_expected_date' => ['required', 'date', 'after_or_equal:purchase_order_date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'shipping_charge' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'products.*.product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'products.*.product_variation_unit_conversion_id' => ['required', Rule::exists('product_variation_unit_conversions', 'product_variation_unit_conversion_id')->where('is_deleted', 0)],
            'products.*.unit_id' => ['required', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'products.*.ordered_quantity' => ['required', 'numeric', 'min:0.0001'],
            'products.*.conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'products.*.unit_price' => ['required', 'numeric', 'min:0'],
            'products.*.total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $this->purchase_order_service->save($obj);
            return redirect('admin/purchase-order')
                ->with('success', empty($request->purchase_order_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'purchase_order_id' => 'required|exists:purchase_orders,purchase_order_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::COMPLETED . ',' . Status::CANCELLED
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->purchase_order_service->status($request->all());
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
}
