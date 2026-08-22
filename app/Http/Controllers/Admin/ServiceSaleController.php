<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\ServiceSaleService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceSaleController extends Controller
{
    use ResponseAPI;

    protected $service_sale_service;
    protected $business_service;
    protected $product_service;
    protected $customer_service;
    protected $document_send_log_service;

    public function __construct(
        ServiceSaleService $service_sale_service,
        ProductService $product_service,
        BusinessService $business_service,
        CustomerService $customer_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:service-sale.view')->only(['index', 'getData', 'byBusiness', 'details']);
        $this->middleware('permission:service-sale.create')->only(['create']);
        $this->middleware('permission:service-sale.create|service-sale.edit')->only(['store']);
        $this->middleware('permission:service-sale.edit')->only(['edit']);
        $this->middleware('permission:service-sale.delete')->only(['destroy']);
        $this->middleware('permission:service-sale.status')->only(['status']);
        $this->middleware('permission:service-sale.print')->only(['print']);
        $this->middleware('module:service-sale');

        $this->service_sale_service = $service_sale_service;
        $this->product_service = $product_service;
        $this->business_service = $business_service;
        $this->customer_service = $customer_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $customers = $this->customer_service->getAllActive();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.service_sale.index', compact('business', 'customers', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->service_sale_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $customers = $this->customer_service->getAllActive();
        $service_sale_no = generateServiceSaleNo();

        return view('admin.service_sale.create', compact('business', 'products', 'customers', 'service_sale_no'));
    }

    public function edit($service_sale_id)
    {
        $service_sale = $this->service_sale_service->getById($service_sale_id);

        if (!$service_sale || $service_sale->status !== Status::PENDING) {
            return redirect('admin/service-sale')
                ->with('error', 'Only pending service sales can be edited.');
        }

        $service_sale_details = $this->service_sale_service->getDetails($service_sale_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $customers = $this->customer_service->getAllActive();

        return view('admin.service_sale.create', compact('service_sale', 'service_sale_details', 'business', 'products', 'customers'));
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
            'customer_id' => ['required', Rule::exists('users', 'id')->where('is_deleted', 0)],
            'service_sale_no' => [
                'required',
                Rule::unique('service_sales', 'service_sale_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->service_sale_id, 'service_sale_id'),
            ],
            'service_sale_date' => ['required', 'date'],
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
            $obj['service_sale_date'] = ($request->service_sale_id)
                ? utcDate($request->service_sale_date, true)
                : utcDate($request->service_sale_date);
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;
            $this->service_sale_service->save($obj);

            return redirect('admin/service-sale')
                ->with('success', empty($request->service_sale_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'service_sale_id' => 'required|exists:service_sales,service_sale_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service_sale_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($service_sale_id)
    {
        try {
            $service_sale = $this->service_sale_service->getDetails($service_sale_id);
            return $this->success(Message::SUCCESS, $service_sale);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function byBusiness($business_id)
    {
        try {
            $service_sales = $this->service_sale_service->getByBusiness($business_id);
            return $this->success(Message::SUCCESS, $service_sales);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function print($service_sale_id)
    {
        $service_sale = $this->service_sale_service->getById($service_sale_id);

        if (!$service_sale) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $service_sale->business_id,
                'service_sale',
                $service_sale_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.service_sale.print.print', compact('service_sale'));
    }

    public function destroy($service_sale_id)
    {
        try {
            $this->service_sale_service->delete($service_sale_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
