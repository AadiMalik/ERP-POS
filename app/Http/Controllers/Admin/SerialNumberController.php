<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\ProductVariationSerialService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SerialNumberController extends Controller
{
    use ResponseAPI;

    protected $product_variation_serial_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;

    public function __construct(
        ProductVariationSerialService $product_variation_serial_service,
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service
    ) {
        $this->middleware('permission:serial-number.view')->only(['index', 'getData', 'show', 'byProduct', 'lookup']);
        $this->middleware('permission:serial-number.create')->only(['addFoundUnit']);
        $this->middleware('permission:serial-number.edit')->only(['sendForRepair', 'returnFromRepair', 'replace']);

        $this->product_variation_serial_service = $product_variation_serial_service;
        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();

        return view('admin.serial_number.index', compact('business', 'products', 'warehouses'));
    }

    public function getData(Request $request)
    {
        return $this->product_variation_serial_service->getData($request->all());
    }

    public function show($product_variation_serial_number_id)
    {
        try {
            $serial = $this->product_variation_serial_service->getFullDetails($product_variation_serial_number_id);
            return view('admin.serial_number.details', compact('serial'));
        } catch (Exception $e) {
            return redirect('admin/serial-number')->with('error', $e->getMessage());
        }
    }

    public function byProduct($product_variation_id)
    {
        try {
            $data = $this->product_variation_serial_service->availableSerialsFor($product_variation_id, null, null, Auth::user()->business_id, 200);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function lookup(Request $request)
    {
        $term = trim((string) $request->input('term'));

        if ($term === '') {
            return $this->success(Message::FETCH, []);
        }

        try {
            $results = \App\Models\ProductVariationSerialNumber::where('business_id', Auth::user()->business_id)
                ->where('serial_no', 'like', '%' . $term . '%')
                ->with(['product', 'productVariation'])
                ->limit(20)
                ->get(['product_variation_serial_number_id', 'serial_no', 'product_id', 'product_variation_id', 'status']);

            return $this->success(Message::FETCH, $results);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function addFoundUnit(Request $request)
    {
        $rules = [
            'product_id' => 'required|exists:products,product_id',
            'product_variation_id' => 'required|exists:product_variations,product_variation_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'serial_no' => 'required|string|max:255',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $warehouse = $this->warehouse_service->getById($request->warehouse_id);

            $serial = $this->product_variation_serial_service->addFoundUnit(
                $warehouse->business_id ?? Auth::user()->business_id,
                $warehouse->branch_id ?? null,
                $request->product_id,
                $request->product_variation_id,
                $request->warehouse_id,
                $request->serial_no,
                $request->unit_cost,
                $request->notes
            );

            return $this->success(Message::SAVE, $serial);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function sendForRepair(Request $request, $product_variation_serial_number_id)
    {
        try {
            $this->product_variation_serial_service->sendForRepair($product_variation_serial_number_id, $request->notes);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function returnFromRepair(Request $request, $product_variation_serial_number_id)
    {
        try {
            $this->product_variation_serial_service->returnFromRepair($product_variation_serial_number_id, $request->notes);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function replace(Request $request, $product_variation_serial_number_id)
    {
        $rules = [
            'new_serial_id' => 'required|exists:product_variation_serial_numbers,product_variation_serial_number_id',
            'notes' => 'nullable|string',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->product_variation_serial_service->replaceSerial($product_variation_serial_number_id, $request->new_serial_id, $request->notes);
            return $this->success(Message::UPDATE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
