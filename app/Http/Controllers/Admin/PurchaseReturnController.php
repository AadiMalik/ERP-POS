<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\PurchaseReturnService;
use App\Services\Concrete\Admin\SupplierService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseReturnController extends Controller
{
    use ResponseAPI;

    protected $purchase_return_service;
    protected $business_service;
    protected $supplier_service;
    protected $warehouse_service;
    protected $document_send_log_service;

    public function __construct(
        PurchaseReturnService $purchase_return_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        WarehouseService $warehouse_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->purchase_return_service = $purchase_return_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->warehouse_service = $warehouse_service;
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
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];
        $return_types = [
            'direct' => 'Direct Purchase',
            'grn'    => 'GRN',
        ];

        return view('admin.purchase_return.index', compact('business', 'suppliers', 'warehouses', 'statuses', 'return_types'));
    }

    public function getData(Request $request)
    {
        return $this->purchase_return_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $direct_purchases = $this->purchase_return_service->getEligibleDirectPurchases();
        $grns = $this->purchase_return_service->getEligibleGrns();
        $purchase_return_no = generatePurchaseReturnNo();

        return view('admin.purchase_return.create', compact('business', 'direct_purchases', 'grns', 'purchase_return_no'));
    }

    public function edit($purchase_return_id)
    {
        $purchase_return = $this->purchase_return_service->getById($purchase_return_id);

        if (!$purchase_return || $purchase_return->status !== Status::PENDING) {
            return redirect('admin/purchase-return')
                ->with('error', 'Only pending purchase returns can be edited.');
        }

        $purchase_return_details = $this->purchase_return_service->getDetails($purchase_return_id);
        $business = $this->business_service->getAll();
        $direct_purchases = $this->purchase_return_service->getEligibleDirectPurchases();
        $grns = $this->purchase_return_service->getEligibleGrns();

        // The return's own source must always be selectable while editing,
        // even if it has since become fully returned by another return.
        if ($purchase_return->return_type === 'direct') {
            if ($purchase_return->purchase && !$direct_purchases->contains('purchase_id', $purchase_return->purchase_id)) {
                $direct_purchases->push($purchase_return->purchase);
            }
        } else {
            if ($purchase_return->goodReceiptNote && !$grns->contains('good_receipt_note_id', $purchase_return->good_receipt_note_id)) {
                $grns->push($purchase_return->goodReceiptNote);
            }
        }

        return view('admin.purchase_return.create', compact('purchase_return', 'purchase_return_details', 'business', 'direct_purchases', 'grns'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'return_type' => ['required', 'in:direct,grn'],
            'purchase_id' => ['required_if:return_type,direct', 'nullable', Rule::exists('purchases', 'purchase_id')->where('is_deleted', 0)],
            'good_receipt_note_id' => ['required_if:return_type,grn', 'nullable', Rule::exists('good_receipt_notes', 'good_receipt_note_id')->where('is_deleted', 0)],
            'purchase_return_no' => [
                'required',
                Rule::unique('purchase_returns', 'purchase_return_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->purchase_return_id, 'purchase_return_id')
            ],
            'purchase_return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.purchase_detail_id' => ['required', Rule::exists('purchase_details', 'purchase_detail_id')],
            'products.*.good_receipt_note_detail_id' => ['nullable', Rule::exists('good_receipt_note_details', 'good_receipt_note_detail_id')],
            'products.*.return_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['purchase_return_date'] = ($request->purchase_return_id)
                ? utcDate($request->purchase_return_date, true)
                : utcDate($request->purchase_return_date);

            $this->purchase_return_service->save($obj);

            return redirect('admin/purchase-return')
                ->with('success', empty($request->purchase_return_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'purchase_return_id' => 'required|exists:purchase_returns,purchase_return_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->purchase_return_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($purchase_return_id)
    {
        try {
            $this->purchase_return_service->delete($purchase_return_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($purchase_return_id)
    {
        try {
            $purchase_return = $this->purchase_return_service->getDetails($purchase_return_id);
            return $this->success(Message::SUCCESS, $purchase_return);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function sourceLines($return_type, $source_id)
    {
        try {
            $data = $this->purchase_return_service->getSourceLines($return_type, $source_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($purchase_return_id)
    {
        $purchase_return = $this->purchase_return_service->getById($purchase_return_id);

        if (!$purchase_return) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $purchase_return->business_id,
                'purchase_return',
                $purchase_return_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.purchase_return.print.print', compact('purchase_return'));
    }
}
