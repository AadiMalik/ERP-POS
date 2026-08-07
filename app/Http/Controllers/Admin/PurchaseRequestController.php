<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\PurchaseRequestService;
use App\Services\Concrete\Admin\SupplierService;
use App\Services\Concrete\Admin\UnitService;
use App\Services\Concrete\Admin\WarehouseService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseRequestController extends Controller
{
    use ResponseAPI;

    protected $purchase_request_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $supplier_service;
    protected $unit_service;
    protected $document_send_log_service;

    public function __construct(
        PurchaseRequestService $purchase_request_service,
        ProductService $product_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        WarehouseService $warehouse_service,
        UnitService $unit_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->purchase_request_service = $purchase_request_service;
        $this->product_service = $product_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->warehouse_service = $warehouse_service;
        $this->unit_service = $unit_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::QUOTATION_SENT => ucfirst(Status::QUOTATION_SENT),
            Status::QUOTATION_RECEIVED => ucfirst(Status::QUOTATION_RECEIVED),
            Status::CONVERTED => ucfirst(Status::CONVERTED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.purchase_request.index', compact('business', 'suppliers', 'warehouses', 'statuses'));
    }
    public function getData(Request $request)
    {
        return $this->purchase_request_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        $purchase_request_no = generatePRNo();

        return view('admin.purchase_request.create', compact('business', 'products', 'suppliers', 'warehouses', 'units', 'purchase_request_no'));
    }

    public function edit($purchase_request_id)
    {
        $purchase_request = $this->purchase_request_service->getById($purchase_request_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();

        return view('admin.purchase_request.create', compact('purchase_request', 'business', 'products', 'suppliers', 'warehouses', 'units'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'supplier_id')->where('is_deleted', 0)],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'purchase_request_no' => [
                'required',
                Rule::unique('purchase_requests', 'purchase_request_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->purchase_request_id, 'purchase_request_id')
            ],
            'purchase_request_date' => ['required', 'date'],
            'purchase_expected_date' => ['required', 'date', 'after_or_equal:purchase_request_date'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'products.*.product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'products.*.unit_id' => ['required', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'products.*.requested_quantity' => ['required', 'numeric', 'min:0.0001']
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['purchase_request_date'] = ($request->purchase_request_id) ? utcDate($request->purchase_request_date, true) : utcDate($request->purchase_request_date);
            $obj['purchase_expected_date'] = ($request->purchase_request_id) ? utcDate($request->purchase_expected_date, true) : utcDate($request->purchase_expected_date);
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $this->purchase_request_service->save($obj);
            return redirect('admin/purchase-request')
                ->with('success', empty($request->purchase_request_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'purchase_request_id' => 'required|exists:purchase_requests,purchase_request_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::QUOTATION_SENT . ',' . Status::QUOTATION_RECEIVED . ',' . Status::CONVERTED . ',' . Status::CANCELLED
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->purchase_request_service->status($request->all());
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
    public function destroy($purchase_request_id)
    {
        try {

            $this->purchase_request_service->delete($purchase_request_id);

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

    public function details($purchase_request_id)
    {
        try {
            $purchase_request = $this->purchase_request_service->getDetails($purchase_request_id);
            return $this->success(Message::SUCCESS, $purchase_request);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $purchase_requests = $this->purchase_request_service->getByBusiness($business_id);
            return $this->success(Message::SUCCESS, $purchase_requests);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function print($purchase_request_id)
    {
        $purchase_request = $this->purchase_request_service->getById($purchase_request_id);

        if (!$purchase_request) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $purchase_request->business_id,
                'purchase_request',
                $purchase_request_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.purchase_request.print.print', compact('purchase_request'));
    }

    //send quotation
    public function sendQuotation(Request $request)
    {
        $rules = [
            'purchase_request_id' => 'required|exists:purchase_requests,purchase_request_id',
            'supplier_ids' => 'required|array|min:1',
            'supplier_ids.*' => 'required|exists:suppliers,supplier_id'
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $purchase_request_quotation = $this->purchase_request_service->sendQuotation($request->all());
            
            if ($purchase_request_quotation['Status']===true) {
                return $this->success(
                    $purchase_request_quotation['Message'] ?? Message::SUCCESS,
                    []
                );
            } else {
                return $this->error($purchase_request_quotation['Message']);
            }
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
