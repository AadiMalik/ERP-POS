<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\ServicePurchaseService;
use App\Services\Concrete\Admin\SupplierService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServicePurchaseController extends Controller
{
    use ResponseAPI;

    protected $service_purchase_service;
    protected $business_service;
    protected $product_service;
    protected $supplier_service;
    protected $document_send_log_service;

    public function __construct(
        ServicePurchaseService $service_purchase_service,
        ProductService $product_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:service-purchase.view')->only(['index', 'getData', 'byBusiness', 'details']);
        $this->middleware('permission:service-purchase.create')->only(['create']);
        $this->middleware('permission:service-purchase.create|service-purchase.edit')->only(['store']);
        $this->middleware('permission:service-purchase.edit')->only(['edit']);
        $this->middleware('permission:service-purchase.delete')->only(['destroy']);
        $this->middleware('permission:service-purchase.status')->only(['status']);
        $this->middleware('permission:service-purchase.print')->only(['print']);
        $this->middleware('module:service-purchase');

        $this->service_purchase_service = $service_purchase_service;
        $this->product_service = $product_service;
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

        return view('admin.service_purchase.index', compact('business', 'suppliers', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->service_purchase_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();
        $service_purchase_no = generateServicePurchaseNo();

        return view('admin.service_purchase.create', compact('business', 'products', 'suppliers', 'service_purchase_no'));
    }

    public function edit($service_purchase_id)
    {
        $service_purchase = $this->service_purchase_service->getById($service_purchase_id);

        if (!$service_purchase || $service_purchase->status !== Status::PENDING) {
            return redirect('admin/service-purchase')
                ->with('error', 'Only pending service purchases can be edited.');
        }

        $service_purchase_details = $this->service_purchase_service->getDetails($service_purchase_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $suppliers = $this->supplier_service->getAllActive();

        return view('admin.service_purchase.create', compact('service_purchase', 'service_purchase_details', 'business', 'products', 'suppliers'));
    }

    public function store(Request $request)
    {
        $items = $request->input('items', []);
        foreach ($items as $index => $item) {
            if (($item['product_id'] ?? '') === '') {
                $items[$index]['product_id'] = null;
            }
        }
        $request->merge(['items' => $items]);

        $validator = Validator::make($request->all(), [
            'supplier_id' => ['required', Rule::exists('suppliers', 'supplier_id')->where('is_deleted', 0)],
            'service_purchase_no' => [
                'required',
                Rule::unique('service_purchases', 'service_purchase_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->service_purchase_id, 'service_purchase_id'),
            ],
            'service_purchase_date' => ['required', 'date'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'items.*.item_name' => ['required', 'string', 'max:191'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax' => ['nullable', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['service_purchase_date'] = ($request->service_purchase_id)
                ? utcDate($request->service_purchase_date, true)
                : utcDate($request->service_purchase_date);
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $this->service_purchase_service->save($obj);

            return redirect('admin/service-purchase')
                ->with('success', empty($request->service_purchase_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'service_purchase_id' => 'required|exists:service_purchases,service_purchase_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service_purchase_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($service_purchase_id)
    {
        try {
            $service_purchase = $this->service_purchase_service->getDetails($service_purchase_id);
            return $this->success(Message::SUCCESS, $service_purchase);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $service_purchases = $this->service_purchase_service->getByBusiness($business_id);
            return $this->success(Message::SUCCESS, $service_purchases);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function print($service_purchase_id)
    {
        $service_purchase = $this->service_purchase_service->getById($service_purchase_id);

        if (!$service_purchase) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $service_purchase->business_id,
                'service_purchase',
                $service_purchase_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.service_purchase.print.print', compact('service_purchase'));
    }

    public function destroy($service_purchase_id)
    {
        try {
            $this->service_purchase_service->delete($service_purchase_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
