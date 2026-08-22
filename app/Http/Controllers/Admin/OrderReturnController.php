<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\OrderReturnService;
use App\Services\Concrete\Admin\PaymentMethodService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class OrderReturnController extends Controller
{
    use ResponseAPI;

    protected $order_return_service;
    protected $business_service;
    protected $customer_service;
    protected $warehouse_service;
    protected $payment_method_service;
    protected $document_send_log_service;

    public function __construct(
        OrderReturnService $order_return_service,
        BusinessService $business_service,
        CustomerService $customer_service,
        WarehouseService $warehouse_service,
        PaymentMethodService $payment_method_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:order-return.view')->only(['index', 'getData', 'details', 'sourceLines']);
        $this->middleware('permission:order-return.create')->only(['create']);
        $this->middleware('permission:order-return.create|order-return.edit')->only(['store']);
        $this->middleware('permission:order-return.edit')->only(['edit']);
        $this->middleware('permission:order-return.delete')->only(['destroy']);
        $this->middleware('permission:order-return.approve')->only(['status']);
        $this->middleware('permission:order-return.print')->only(['print']);

        $this->order_return_service = $order_return_service;
        $this->business_service = $business_service;
        $this->customer_service = $customer_service;
        $this->warehouse_service = $warehouse_service;
        $this->payment_method_service = $payment_method_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $customers = $this->customer_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.order_return.index', compact('business', 'customers', 'warehouses', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->order_return_service->getData($request->all());
    }

    public function create(Request $request)
    {
        $business = $this->business_service->getAll();
        $orders = $this->order_return_service->getEligibleOrders();
        $payment_methods = $this->payment_method_service->getAllActive();
        $order_return_no = generateOrderReturnNo();
        // Prefills the Order dropdown when arriving from the "Return" button
        // on the Order List/Order Show pages (admin/order-return/create?order_id=...).
        $preselected_order_id = $request->query('order_id');

        return view('admin.order_return.create', compact('business', 'orders', 'payment_methods', 'order_return_no', 'preselected_order_id'));
    }

    public function edit($order_return_id)
    {
        $order_return = $this->order_return_service->getById($order_return_id);

        if (!$order_return || $order_return->status !== Status::PENDING) {
            return redirect('admin/order-return')
                ->with('error', 'Only pending order returns can be edited.');
        }

        $order_return_details = $this->order_return_service->getDetails($order_return_id);
        $business = $this->business_service->getAll();
        $orders = $this->order_return_service->getEligibleOrders();
        $payment_methods = $this->payment_method_service->getAllActive();

        // The return's own source order must always be selectable while
        // editing, even if it has since become fully returned by another
        // return.
        if ($order_return->order && !$orders->contains('order_id', $order_return->order_id)) {
            $orders->push($order_return->order);
        }

        return view('admin.order_return.create', compact('order_return', 'order_return_details', 'business', 'orders', 'payment_methods'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => ['required', Rule::exists('orders', 'order_id')->where('is_deleted', 0)],
            'order_return_no' => [
                'required',
                Rule::unique('order_returns', 'order_return_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->order_return_id, 'order_return_id')
            ],
            'order_return_date' => ['required', 'date'],
            'refund_payment_method_id' => ['nullable', Rule::exists('payment_methods', 'payment_method_id')],
            'reason' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.order_detail_id' => ['required', Rule::exists('order_details', 'order_detail_id')],
            'products.*.return_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['order_return_date'] = ($request->order_return_id)
                ? utcDate($request->order_return_date, true)
                : utcDate($request->order_return_date);

            $this->order_return_service->save($obj);

            return redirect('admin/order-return')
                ->with('success', empty($request->order_return_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'order_return_id' => 'required|exists:order_returns,order_return_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->order_return_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($order_return_id)
    {
        try {
            $this->order_return_service->delete($order_return_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($order_return_id)
    {
        try {
            $order_return = $this->order_return_service->getDetails($order_return_id);
            return $this->success(Message::SUCCESS, $order_return);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function sourceLines($order_id)
    {
        try {
            $data = $this->order_return_service->getSourceLines($order_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($order_return_id)
    {
        $order_return = $this->order_return_service->getById($order_return_id);

        if (!$order_return) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $order_return->business_id,
                'order_return',
                $order_return_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.order_return.print.print', compact('order_return'));
    }
}
