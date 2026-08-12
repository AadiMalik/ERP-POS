<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\StockTakingService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class StockTakingController extends Controller
{
    use ResponseAPI;

    protected $stock_taking_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $document_send_log_service;

    public function __construct(
        StockTakingService $stock_taking_service,
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->stock_taking_service = $stock_taking_service;
        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
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

        return view('admin.stock_taking.index', compact('business', 'warehouses', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->stock_taking_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $stock_taking_no = generateStockTakingNo();

        return view('admin.stock_taking.create', compact('business', 'products', 'warehouses', 'stock_taking_no'));
    }

    public function edit($stock_taking_id)
    {
        $stock_taking = $this->stock_taking_service->getById($stock_taking_id);

        if (!$stock_taking || $stock_taking->status !== Status::PENDING) {
            return redirect('admin/stock-taking')
                ->with('error', 'Only pending stock takings can be edited.');
        }

        $stock_taking_details = $this->stock_taking_service->getDetails($stock_taking_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();

        return view('admin.stock_taking.create', compact('stock_taking', 'stock_taking_details', 'business', 'products', 'warehouses'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'stock_taking_no' => [
                'required',
                Rule::unique('stock_takings', 'stock_taking_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->stock_taking_id, 'stock_taking_id')
            ],
            'stock_taking_date' => ['required', 'date'],
            'reference' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'products.*.product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'products.*.unit_id' => ['nullable', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'products.*.physical_quantity' => ['required', 'numeric', 'min:0'],
            'products.*.reason' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['stock_taking_date'] = ($request->stock_taking_id)
                ? utcDate($request->stock_taking_date, true)
                : utcDate($request->stock_taking_date);

            $this->stock_taking_service->save($obj);

            return redirect('admin/stock-taking')
                ->with('success', empty($request->stock_taking_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'stock_taking_id' => 'required|exists:stock_takings,stock_taking_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->stock_taking_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($stock_taking_id)
    {
        try {
            $this->stock_taking_service->delete($stock_taking_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($stock_taking_id)
    {
        try {
            $stock_taking = $this->stock_taking_service->getDetails($stock_taking_id);
            return $this->success(Message::SUCCESS, $stock_taking);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function systemStock($warehouse_id)
    {
        try {
            $data = $this->stock_taking_service->getSystemStock($warehouse_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($stock_taking_id)
    {
        $stock_taking = $this->stock_taking_service->getById($stock_taking_id);

        if (!$stock_taking) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $stock_taking->business_id,
                'stock_taking',
                $stock_taking_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.stock_taking.print.print', compact('stock_taking'));
    }
}
