<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\PurchaseOrderService;
use App\Services\Concrete\Admin\SupplierService;
use App\Services\Concrete\Admin\UnitService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
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
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();

        return view('admin.purchase_order.index', compact('business', 'products','suppliers','warehouses'));
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        $purchase_order_no = generatePONo();

        return view('admin.purchase_order.create', compact('business', 'products','suppliers','warehouses','units','purchase_order_no'));
    }

    public function store(Request $request)
    {
        dd($request->all());
        $validator = Validator::make($request->all(), [
            'business_id' => ['required', Rule::exists('businesses', 'business_id')->where('is_deleted', 0)],
            'supplier_id' => ['required', Rule::exists('suppliers', 'supplier_id')->where('is_deleted', 0)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'date' => ['required'],
            'items.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'items.*.quantity' => ['required', 'numeric'],
            'items.*.unit_id' => ['required', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'items.*.unit_price' => ['required', 'numeric'],
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->first());
        }

        try {
            $this->purchase_order_service->save($request->all());
            return $this->success(Message::SAVE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
