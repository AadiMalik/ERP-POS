<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\PurchaseService;
use App\Services\Concrete\Admin\PurchaseRequestService;
use App\Services\Concrete\Admin\SupplierService;
use App\Services\Concrete\Admin\UnitService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    use ResponseAPI;

    protected $purchase_service;
    protected $purchase_request_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $supplier_service;
    protected $unit_service;

    public function __construct(
        PurchaseService $purchase_service,
        PurchaseRequestService $purchase_request_service,
        ProductService $product_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        WarehouseService $warehouse_service,
        UnitService $unit_service
    ) {
        $this->purchase_service = $purchase_service;
        $this->purchase_request_service = $purchase_request_service;
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
        $purchase_requests = $this->purchase_request_service->getAllApproved();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::COMPLETED => ucfirst(Status::COMPLETED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.purchase.index', compact('business', 'suppliers', 'purchase_requests', 'warehouses', 'statuses'));
    }
    public function getData(Request $request)
    {
        return $this->purchase_service->getData($request->all());
    }

    public function create()
    {
        
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        $purchase_requests = $this->purchase_request_service->getAllApproved();
        $purchase_no = generatePONo();

        return view('admin.purchase.create', compact('business', 'products', 'suppliers', 'purchase_requests', 'warehouses', 'units', 'purchase_no'));
    }

    public function edit($purchase_id)
    {
        $purchase = $this->purchase_service->getById($purchase_id);

        if (!$purchase || $purchase->status !== Status::PENDING) {
            return redirect('admin/purchase')
                ->with('error', 'Only pending purchases can be edited.');
        }

        $purchase_details = $this->purchase_service->getDetails($purchase_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        $purchase_requests = $this->purchase_request_service->getAllApproved();

        return view('admin.purchase.create', compact('purchase', 'purchase_details', 'business', 'products', 'purchase_requests', 'suppliers', 'warehouses', 'units'));
    }

    public function store(Request $request)
    {
        // Conversion is optional; the form posts an empty string when none is
        // selected, so normalize it to null before validation runs the
        // "exists" check on product_variation_unit_conversion_id.
        $products = $request->input('products', []);
        foreach ($products as $index => $product) {
            if (($product['product_variation_unit_conversion_id'] ?? '') === '') {
                $products[$index]['product_variation_unit_conversion_id'] = null;
            }
        }
        $request->merge(['products' => $products]);

        $validator = Validator::make($request->all(), [
            'supplier_id' => ['required', Rule::exists('suppliers', 'supplier_id')->where('is_deleted', 0)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'purchase_no' => [
                'required',
                Rule::unique('purchases', 'purchase_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->purchase_id, 'purchase_id')
            ],
            'purchase_date' => ['required', 'date'],
            'expected_delivery_date' => ['required', 'date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'shipping_charge' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'products.*.product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'products.*.product_variation_unit_conversion_id' => ['nullable', Rule::exists('product_variation_unit_conversions', 'product_variation_unit_conversion_id')->where('is_deleted', 0)],
            'products.*.unit_id' => ['required', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'products.*.ordered_quantity' => ['required', 'numeric', 'min:0.0001'],
            'products.*.conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'products.*.unit_price' => ['required', 'numeric', 'min:0'],
            'products.*.received_quantity' => ['nullable', 'numeric', 'min:0'],
            'products.*.subtotal' => ['required', 'numeric', 'min:0'],
            'products.*.discount' => ['required', 'numeric', 'min:0'],
            'products.*.discount_amount' => ['required', 'numeric', 'min:0'],
            'products.*.tax' => ['required', 'numeric', 'min:0'],
            'products.*.tax_amount' => ['required', 'numeric', 'min:0'],
            'products.*.total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['purchase_date'] = ($request->purchase_id) ? utcDate($request->purchase_date, true) : utcDate($request->purchase_date);
            $obj['expected_delivery_date'] = ($request->purchase_id) ? utcDate($request->expected_delivery_date, true) : utcDate($request->expected_delivery_date);
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $this->purchase_service->save($obj);
            return redirect('admin/purchase')
                ->with('success', empty($request->purchase_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'purchase_id' => 'required|exists:purchases,purchase_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::COMPLETED . ',' . Status::CANCELLED
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->purchase_service->status($request->all());
            return $this->success(
                Message::STATUS,
                []
            );
        } catch (Exception $e) {
            return $this->error(
                $e->getMessage()
                // Message::ERROR
            );
        }
    }

    public function details($purchase_id)
    {
        try {
            $purchase = $this->purchase_service->getDetails($purchase_id);
            return $this->success(Message::SUCCESS, $purchase);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $purchases = $this->purchase_service->getByBusiness($business_id);
            return $this->success(Message::SUCCESS, $purchases);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function destroy($purchase_id)
    {
        try {
            $this->purchase_service->delete($purchase_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
