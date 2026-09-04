<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LossType;
use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\LossReasonService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Services\Concrete\Admin\WasteDamageExpiryService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WasteDamageExpiryController extends Controller
{
    use ResponseAPI;

    protected $waste_damage_expiry_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $loss_reason_service;
    protected $document_send_log_service;

    public function __construct(
        WasteDamageExpiryService $waste_damage_expiry_service,
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service,
        LossReasonService $loss_reason_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:waste-damage-expiry.view')->only(['index', 'getData', 'details', 'batches', 'stock', 'serials']);
        $this->middleware('permission:waste-damage-expiry.create')->only(['create']);
        $this->middleware('permission:waste-damage-expiry.create|waste-damage-expiry.edit')->only(['store']);
        $this->middleware('permission:waste-damage-expiry.edit')->only(['edit']);
        $this->middleware('permission:waste-damage-expiry.delete')->only(['destroy']);
        $this->middleware('permission:waste-damage-expiry.approve|waste-damage-expiry.cancel')->only(['status']);
        $this->middleware('permission:waste-damage-expiry.print')->only(['print']);

        $this->waste_damage_expiry_service = $waste_damage_expiry_service;
        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
        $this->loss_reason_service = $loss_reason_service;
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

        return view('admin.waste_damage_expiry.index', compact('business', 'warehouses', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->waste_damage_expiry_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $loss_reasons = $this->loss_reason_service->getActiveByBusiness(Auth::user()->business_id);
        $loss_types = LossType::getOptions();
        $reference_no = generateWasteDamageExpiryNo();

        return view('admin.waste_damage_expiry.create', compact('business', 'products', 'warehouses', 'loss_reasons', 'loss_types', 'reference_no'));
    }

    public function edit($waste_damage_expiry_id)
    {
        $waste_damage_expiry = $this->waste_damage_expiry_service->getById($waste_damage_expiry_id);

        if (!$waste_damage_expiry || $waste_damage_expiry->status !== Status::PENDING) {
            return redirect('admin/waste-damage-expiry')
                ->with('error', 'Only pending records can be edited.');
        }

        $waste_damage_expiry_details = $this->waste_damage_expiry_service->getDetails($waste_damage_expiry_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $loss_reasons = $this->loss_reason_service->getActiveByBusiness($waste_damage_expiry->business_id);
        $loss_types = LossType::getOptions();

        return view('admin.waste_damage_expiry.create', compact(
            'waste_damage_expiry', 'waste_damage_expiry_details', 'business', 'products', 'warehouses', 'loss_reasons', 'loss_types'
        ));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'reference_no' => [
                'required',
                Rule::unique('waste_damage_expiries', 'reference_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->waste_damage_expiry_id, 'waste_damage_expiry_id'),
            ],
            'transaction_date' => ['required', 'date'],
            'reference' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'lines.*.product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'lines.*.unit_id' => ['nullable', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'lines.*.product_variation_batch_id' => ['nullable', Rule::exists('product_variation_batches', 'product_variation_batch_id')->where('is_deleted', 0)],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.loss_type' => ['required', Rule::in(array_keys(LossType::getOptions()))],
            'lines.*.loss_reason_id' => ['nullable', Rule::exists('loss_reasons', 'loss_reason_id')->where('is_deleted', 0)],
            'lines.*.notes' => ['nullable', 'string'],
            'lines.*.serial_numbers' => ['nullable', 'array'],
            'lines.*.serial_numbers.*' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['transaction_date'] = ($request->waste_damage_expiry_id)
                ? utcDate($request->transaction_date, true)
                : utcDate($request->transaction_date);

            $this->waste_damage_expiry_service->save($obj);

            return redirect('admin/waste-damage-expiry')
                ->with('success', empty($request->waste_damage_expiry_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'waste_damage_expiry_id' => 'required|exists:waste_damage_expiries,waste_damage_expiry_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->waste_damage_expiry_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($waste_damage_expiry_id)
    {
        try {
            $this->waste_damage_expiry_service->delete($waste_damage_expiry_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($waste_damage_expiry_id)
    {
        try {
            $waste_damage_expiry = $this->waste_damage_expiry_service->getDetails($waste_damage_expiry_id);
            return $this->success(Message::SUCCESS, $waste_damage_expiry);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function batches($warehouse_id, $product_variation_id)
    {
        try {
            $data = $this->waste_damage_expiry_service->getBatches($warehouse_id, $product_variation_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function stock($warehouse_id, $product_variation_id)
    {
        try {
            $data = $this->waste_damage_expiry_service->getStock($warehouse_id, $product_variation_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function serials($warehouse_id, $product_variation_id)
    {
        try {
            $data = $this->waste_damage_expiry_service->getSerials($warehouse_id, $product_variation_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($waste_damage_expiry_id)
    {
        $waste_damage_expiry = $this->waste_damage_expiry_service->getById($waste_damage_expiry_id);

        if (!$waste_damage_expiry) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $waste_damage_expiry->business_id,
                'waste_damage_expiry',
                $waste_damage_expiry_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.waste_damage_expiry.print.print', compact('waste_damage_expiry'));
    }
}
