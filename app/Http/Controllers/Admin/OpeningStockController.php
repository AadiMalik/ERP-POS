<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\OpeningStockService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\UnitService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OpeningStockController extends Controller
{
    use ResponseAPI;

    protected $opening_stock_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $unit_service;
    protected $document_send_log_service;

    public function __construct(
        OpeningStockService $opening_stock_service,
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service,
        UnitService $unit_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:opening-stock.view')->only(['index', 'getData', 'details']);
        $this->middleware('permission:opening-stock.create')->only(['create']);
        $this->middleware('permission:opening-stock.create|opening-stock.edit')->only(['store']);
        $this->middleware('permission:opening-stock.edit')->only(['edit']);
        $this->middleware('permission:opening-stock.delete')->only(['destroy']);
        $this->middleware('permission:opening-stock.status')->only(['status']);
        $this->middleware('permission:opening-stock.print')->only(['print']);

        $this->opening_stock_service = $opening_stock_service;
        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
        $this->unit_service = $unit_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $warehouses = $this->warehouse_service->getAllActive();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.opening_stock.index', compact('business', 'warehouses', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->opening_stock_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        $opening_stock_no = generateOpeningStockNo();

        return view('admin.opening_stock.create', compact('business', 'products', 'warehouses', 'units', 'opening_stock_no'));
    }

    public function edit($opening_stock_id)
    {
        $opening_stock = $this->opening_stock_service->getById($opening_stock_id);

        if (!$opening_stock || $opening_stock->status !== Status::PENDING) {
            return redirect('admin/opening-stock')
                ->with('error', 'Only pending opening stocks can be edited.');
        }

        $opening_stock_details = $this->opening_stock_service->getDetails($opening_stock_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();

        return view('admin.opening_stock.create', compact('opening_stock', 'opening_stock_details', 'business', 'products', 'warehouses', 'units'));
    }

    public function store(Request $request)
    {
        $products = $request->input('products', []);
        foreach ($products as $index => $product) {
            if (($product['product_variation_unit_conversion_id'] ?? '') === '') {
                $products[$index]['product_variation_unit_conversion_id'] = null;
            }
            if (!empty($product['expiry_date'])) {
                $products[$index]['expiry_date'] = utcDate($product['expiry_date']);
            }
        }
        $request->merge(['products' => $products]);

        $validator = Validator::make($request->all(), [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'opening_stock_no' => [
                'required',
                Rule::unique('opening_stocks', 'opening_stock_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->opening_stock_id, 'opening_stock_id')
            ],
            'opening_stock_date' => ['required', 'date'],
            'reference' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'products.*.product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'products.*.product_variation_unit_conversion_id' => ['nullable', Rule::exists('product_variation_unit_conversions', 'product_variation_unit_conversion_id')->where('is_deleted', 0)],
            'products.*.unit_id' => ['required', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'products.*.quantity' => ['required', 'numeric', 'min:0'],
            'products.*.conversion_factor' => ['required', 'numeric', 'min:0.0001'],
            'products.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'products.*.batch_no' => ['nullable', 'string'],
            'products.*.expiry_date' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['opening_stock_date'] = ($request->opening_stock_id)
                ? utcDate($request->opening_stock_date, true)
                : utcDate($request->opening_stock_date);

            $this->opening_stock_service->save($obj);

            return redirect('admin/opening-stock')
                ->with('success', empty($request->opening_stock_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'opening_stock_id' => 'required|exists:opening_stocks,opening_stock_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->opening_stock_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($opening_stock_id)
    {
        try {
            $this->opening_stock_service->delete($opening_stock_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($opening_stock_id)
    {
        try {
            $opening_stock = $this->opening_stock_service->getDetails($opening_stock_id);
            return $this->success(Message::SUCCESS, $opening_stock);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function print($opening_stock_id)
    {
        $opening_stock = $this->opening_stock_service->getById($opening_stock_id);

        if (!$opening_stock) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $opening_stock->business_id,
                'opening_stock',
                $opening_stock_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.opening_stock.print.print', compact('opening_stock'));
    }
}
