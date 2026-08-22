<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\ServicePurchaseReturnService;
use App\Services\Concrete\Admin\SupplierService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServicePurchaseReturnController extends Controller
{
    use ResponseAPI;

    protected $service_purchase_return_service;
    protected $business_service;
    protected $supplier_service;
    protected $document_send_log_service;

    public function __construct(
        ServicePurchaseReturnService $service_purchase_return_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:service-purchase-return.view')->only(['index', 'getData', 'details', 'sourceLines']);
        $this->middleware('permission:service-purchase-return.create')->only(['create']);
        $this->middleware('permission:service-purchase-return.create|service-purchase-return.edit')->only(['store']);
        $this->middleware('permission:service-purchase-return.edit')->only(['edit']);
        $this->middleware('permission:service-purchase-return.delete')->only(['destroy']);
        $this->middleware('permission:service-purchase-return.approve')->only(['status']);
        $this->middleware('permission:service-purchase-return.print')->only(['print']);

        $this->service_purchase_return_service = $service_purchase_return_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.service_purchase_return.index', compact('business', 'suppliers', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->service_purchase_return_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $service_purchases = $this->service_purchase_return_service->getEligibleServicePurchases();
        $service_purchase_return_no = generateServicePurchaseReturnNo();

        return view('admin.service_purchase_return.create', compact('business', 'service_purchases', 'service_purchase_return_no'));
    }

    public function edit($service_purchase_return_id)
    {
        $service_purchase_return = $this->service_purchase_return_service->getById($service_purchase_return_id);

        if (!$service_purchase_return || $service_purchase_return->status !== Status::PENDING) {
            return redirect('admin/service-purchase-return')
                ->with('error', 'Only pending service purchase returns can be edited.');
        }

        $service_purchase_return_details = $this->service_purchase_return_service->getDetails($service_purchase_return_id);
        $business = $this->business_service->getAll();
        $service_purchases = $this->service_purchase_return_service->getEligibleServicePurchases();

        // The return's own source must always be selectable while editing,
        // even if it has since become fully returned by another return.
        if ($service_purchase_return->servicePurchase && !$service_purchases->contains('service_purchase_id', $service_purchase_return->service_purchase_id)) {
            $service_purchases->push($service_purchase_return->servicePurchase);
        }

        return view('admin.service_purchase_return.create', compact('service_purchase_return', 'service_purchase_return_details', 'business', 'service_purchases'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_purchase_id' => ['required', Rule::exists('service_purchases', 'service_purchase_id')->where('is_deleted', 0)],
            'service_purchase_return_no' => [
                'required',
                Rule::unique('service_purchase_returns', 'service_purchase_return_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->service_purchase_return_id, 'service_purchase_return_id'),
            ],
            'service_purchase_return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_purchase_detail_id' => ['required', Rule::exists('service_purchase_details', 'service_purchase_detail_id')],
            'items.*.return_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['service_purchase_return_date'] = ($request->service_purchase_return_id)
                ? utcDate($request->service_purchase_return_date, true)
                : utcDate($request->service_purchase_return_date);

            $this->service_purchase_return_service->save($obj);

            return redirect('admin/service-purchase-return')
                ->with('success', empty($request->service_purchase_return_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'service_purchase_return_id' => 'required|exists:service_purchase_returns,service_purchase_return_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service_purchase_return_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($service_purchase_return_id)
    {
        try {
            $this->service_purchase_return_service->delete($service_purchase_return_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($service_purchase_return_id)
    {
        try {
            $service_purchase_return = $this->service_purchase_return_service->getDetails($service_purchase_return_id);
            return $this->success(Message::SUCCESS, $service_purchase_return);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function sourceLines($service_purchase_id)
    {
        try {
            $data = $this->service_purchase_return_service->getSourceLines($service_purchase_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($service_purchase_return_id)
    {
        $service_purchase_return = $this->service_purchase_return_service->getById($service_purchase_return_id);

        if (!$service_purchase_return) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $service_purchase_return->business_id,
                'service_purchase_return',
                $service_purchase_return_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.service_purchase_return.print.print', compact('service_purchase_return'));
    }
}
