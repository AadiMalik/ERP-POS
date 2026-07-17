<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\PurchaseRequestQuotationService;
use App\Services\Concrete\Admin\PurchaseRequestService;
use App\Services\Concrete\Admin\SupplierService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseRequestQuotationController extends Controller
{
    use ResponseAPI;

    protected $purchase_request_service;
    protected $purchase_request_quotation_service;
    protected $business_service;
    protected $product_service;
    protected $supplier_service;

    public function __construct(
        PurchaseRequestService $purchase_request_service,
        PurchaseRequestQuotationService $purchase_request_quotation_service,
        ProductService $product_service,
        BusinessService $business_service,
        SupplierService $supplier_service
    ) {
        $this->purchase_request_quotation_service = $purchase_request_quotation_service;
        $this->purchase_request_service = $purchase_request_service;
        $this->product_service = $product_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
    }

    public function index(Request $request)
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $purchase_requests = $this->purchase_request_service->getAllApproved();
        $purchase_request_id = $request->purchase_request_id ?? null;
        $statuses = [
            Status::SENT   => ucfirst(Status::SENT),
            Status::RECEIVED  => ucfirst(Status::RECEIVED),
            Status::SELECTED => ucfirst(Status::SELECTED),
            Status::REJECTED => ucfirst(Status::REJECTED),
        ];

        return view('admin.purchase_request_quotation.index', compact('business', 'purchase_requests', 'purchase_request_id', 'suppliers', 'statuses'));
    }
    public function getData(Request $request)
    {
        return $this->purchase_request_quotation_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $purchase_requests = $this->purchase_request_service->getAllApproved();
        $purchase_request_quotation_no = generateQuotationNo();

        return view('admin.purchase_request_quotation.create', compact('business', 'suppliers', 'purchase_requests',  'purchase_request_quotation_no'));
    }

    public function edit($purchase_request_quotation_id)
    {
        $purchase_request_quotation = $this->purchase_request_quotation_service->getById($purchase_request_quotation_id);
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $purchase_requests = $this->purchase_request_service->getAllApproved();

        return view('admin.purchase_request_quotation.create', compact('purchase_request_quotation', 'business', 'purchase_requests', 'suppliers'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'received_date' => ($request->purchase_request_quotation_id) ? utcDate($request->received_date, true) : utcDate($request->received_date),
        ]);
        $validator = Validator::make($request->all(), [
            'supplier_id' => ['required', Rule::exists('suppliers', 'supplier_id')->where('is_deleted', 0)],
            'purchase_request_id' => [
                'required',
                Rule::exists('purchase_requests', 'purchase_request_id')
                    ->where('is_deleted', 0),

                Rule::unique('purchase_request_quotations')
                    ->where(function ($query) use ($request) {
                        $query->where('purchase_request_id', $request->purchase_request_id)
                            ->where('supplier_id', $request->supplier_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore(
                        $request->purchase_request_quotation_id,
                        'purchase_request_quotation_id'
                    ),
            ],
            'purchase_request_quotation_no' => [
                'required',
                Rule::unique('purchase_request_quotations', 'purchase_request_quotation_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->purchase_request_quotation_id, 'purchase_request_quotation_id')
            ],
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
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $obj['send_email'] = $request->send_email === 'on' ? 1 : 0;
            $obj['send_sms'] = $request->send_sms  === 'on' ? 1 : 0;
            $obj['send_whatsapp'] = $request->send_whatsapp  === 'on' ? 1 : 0;
            $purchase_request_quotation = $this->purchase_request_quotation_service->save($obj);

            if ($purchase_request_quotation['Status'] === true) {
                return redirect('admin/purchase-request-quotation')
                    ->with('success', empty($request->purchase_request_quotation_id) ? Message::SAVE : Message::UPDATE);
            } else {
                return redirect()->back()->with('error', $purchase_request_quotation['Message']);
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'purchase_request_quotation_id' => 'required|exists:purchase_request_quotations,purchase_request_quotation_id',
            'status' => 'required|in:' . Status::SENT . ',' . Status::RECEIVED . ',' . Status::SELECTED . ',' . Status::REJECTED
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->purchase_request_quotation_service->status($request->all());
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
    public function destroy($purchase_request_quotation_id)
    {
        try {

            $this->purchase_request_quotation_service->delete($purchase_request_quotation_id);

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

    public function details($purchase_request_quotation_id)
    {
        try {
            $purchase_request_quotation = $this->purchase_request_quotation_service->getDetails($purchase_request_quotation_id);
            return $this->success(Message::SUCCESS, $purchase_request_quotation);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $purchase_request_quotations = $this->purchase_request_quotation_service->getByBusiness($business_id);
            return $this->success(Message::SUCCESS, $purchase_request_quotations);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }

    public function byPurchaseRequest($purchase_request_id)
    {
        try {
            $purchase_request_quotations = $this->purchase_request_quotation_service->getByPurchaseRequest($purchase_request_id);
            return $this->success(Message::SUCCESS, $purchase_request_quotations);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
    public function selectedByPurchaseRequest($purchase_request_id)
    {
        try {
            $purchase_request_quotations = $this->purchase_request_quotation_service->getSelectedByPurchaseRequest($purchase_request_id);
            return $this->success(Message::SUCCESS, $purchase_request_quotations);
        } catch (Exception $e) {
            return $this->error(
                Message::ERROR
            );
        }
    }
}
