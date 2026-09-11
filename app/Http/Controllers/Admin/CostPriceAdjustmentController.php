<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CostPriceAdjustmentService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CostPriceAdjustmentController extends Controller
{
    use ResponseAPI;

    protected $cost_price_adjustment_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $document_send_log_service;

    public function __construct(
        CostPriceAdjustmentService $cost_price_adjustment_service,
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:cost-price-adjustment.view')->only(['index', 'getData', 'details', 'stock', 'batches']);
        $this->middleware('permission:cost-price-adjustment.create')->only(['create']);
        $this->middleware('permission:cost-price-adjustment.create|cost-price-adjustment.edit')->only(['store']);
        $this->middleware('permission:cost-price-adjustment.edit')->only(['edit']);
        $this->middleware('permission:cost-price-adjustment.delete')->only(['destroy']);
        $this->middleware('permission:cost-price-adjustment.approve|cost-price-adjustment.cancel')->only(['status']);
        $this->middleware('permission:cost-price-adjustment.print')->only(['print']);

        $this->cost_price_adjustment_service = $cost_price_adjustment_service;
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

        return view('admin.cost_price_adjustment.index', compact('business', 'warehouses', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->cost_price_adjustment_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $reference_no = generateCostPriceAdjustmentNo();

        return view('admin.cost_price_adjustment.create', compact('business', 'products', 'warehouses', 'reference_no'));
    }

    public function edit($cost_price_adjustment_id)
    {
        $cost_price_adjustment = $this->cost_price_adjustment_service->getById($cost_price_adjustment_id);

        if (!$cost_price_adjustment || $cost_price_adjustment->status !== Status::PENDING) {
            return redirect('admin/cost-price-adjustment')
                ->with('error', __('cost_price_adjustment.only_pending_can_be_edited'));
        }

        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();

        return view('admin.cost_price_adjustment.create', compact(
            'cost_price_adjustment', 'business', 'products', 'warehouses'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'reference_no' => [
                'required',
                Rule::unique('cost_price_adjustments', 'reference_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->cost_price_adjustment_id, 'cost_price_adjustment_id'),
            ],
            'adjustment_date' => ['required', 'date'],
            'new_cost_price' => ['required', 'numeric', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'reference' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['adjustment_date'] = utcDate($request->adjustment_date);

            $this->cost_price_adjustment_service->save($obj);

            return redirect('admin/cost-price-adjustment')
                ->with('success', empty($request->cost_price_adjustment_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'cost_price_adjustment_id' => 'required|exists:cost_price_adjustments,cost_price_adjustment_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->cost_price_adjustment_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($cost_price_adjustment_id)
    {
        try {
            $this->cost_price_adjustment_service->delete($cost_price_adjustment_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($cost_price_adjustment_id)
    {
        try {
            $data = $this->cost_price_adjustment_service->getDetails($cost_price_adjustment_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function stock($warehouse_id, $product_variation_id)
    {
        try {
            $data = $this->cost_price_adjustment_service->getStock($warehouse_id, $product_variation_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function batches($warehouse_id, $product_variation_id)
    {
        try {
            $data = $this->cost_price_adjustment_service->getBatches($warehouse_id, $product_variation_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($cost_price_adjustment_id)
    {
        $cost_price_adjustment = $this->cost_price_adjustment_service->getById($cost_price_adjustment_id);

        if (!$cost_price_adjustment) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $cost_price_adjustment->business_id,
                'cost_price_adjustment',
                $cost_price_adjustment_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.cost_price_adjustment.print.print', compact('cost_price_adjustment'));
    }
}
