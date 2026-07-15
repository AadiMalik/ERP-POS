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

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $statuses = [
            Status::SENT   => ucfirst(Status::SENT),
            Status::RECEIVED  => ucfirst(Status::RECEIVED),
            Status::SELECTED => ucfirst(Status::SELECTED),
            Status::REJECTED => ucfirst(Status::REJECTED),
        ];

        return view('admin.purchase_request_quotation.index', compact('business', 'suppliers','statuses'));
    }
    public function getData(Request $request)
    {
        return $this->purchase_request_quotation_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $purchase_request_quotation_no = generateQuotationNo();

        return view('admin.purchase_request_quotation.create', compact('business', 'products', 'suppliers', 'purchase_request_quotation_no'));
    }

    public function edit($purchase_request_quotation_id)
    {
        $purchase_request_quotation = $this->purchase_request_quotation_service->getById($purchase_request_quotation_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();

        return view('admin.purchase_request_quotation.create', compact('purchase_request_quotation', 'business', 'products', 'suppliers'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'sent_date' => ($request->purchase_request_quotation_id) ? utcDate($request->sent_date, true) : utcDate($request->sent_date),
            'received_date' => ($request->purchase_request_quotation_id) ? utcDate($request->received_date, true) : utcDate($request->received_date),
        ]);
        $validator = Validator::make($request->all(), [
            'supplier_id' => ['required', Rule::exists('suppliers', 'supplier_id')->where('is_deleted', 0)],
            'purchase_request_quotation_no' => [
                'required',
                Rule::unique('purchase_request_quotations', 'purchase_request_quotation_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->purchase_request_quotation_id, 'purchase_request_quotation_id')
            ],
            'sent_date' => ['required', 'date'],
            'received_date' => ['required', 'date', 'after_or_equal:sent_date'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'products.*.product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'products.*.unit_id' => ['required', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'products.*.requested_quantity' => ['required', 'numeric', 'min:0.0001'],
            'products.*.quoted_quantity' => ['required', 'numeric', 'min:0.0001'],
            'products.*.unit_price' => ['required', 'numeric', 'min:0.0001'],
            'products.*.discount' => ['required', 'numeric', 'min:0'],
            'products.*.discount_amount' => ['required', 'numeric', 'min:0'],
            'products.*.tax' => ['required', 'numeric', 'min:0'],
            'products.*.tax_amount' => ['required', 'numeric', 'min:0'],
            'products.*.subtotal' => ['required', 'numeric', 'min:0.0001'],
            'products.*.total' => ['required', 'numeric', 'min:0.0001'],
            'products.*.discription' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $this->purchase_request_quotation_service->save($obj);
            return redirect('admin/purchase-request-quotation')
                ->with('success', empty($request->purchase_request_quotation_id) ? Message::SAVE : Message::UPDATE);
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
