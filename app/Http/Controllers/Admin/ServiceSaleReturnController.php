<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\ServiceSaleReturnService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ServiceSaleReturnController extends Controller
{
    use ResponseAPI;

    protected $service_sale_return_service;
    protected $business_service;
    protected $customer_service;
    protected $document_send_log_service;

    public function __construct(
        ServiceSaleReturnService $service_sale_return_service,
        BusinessService $business_service,
        CustomerService $customer_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:service-sale-return.view')->only(['index', 'getData', 'details', 'sourceLines']);
        $this->middleware('permission:service-sale-return.create')->only(['create']);
        $this->middleware('permission:service-sale-return.create|service-sale-return.edit')->only(['store']);
        $this->middleware('permission:service-sale-return.edit')->only(['edit']);
        $this->middleware('permission:service-sale-return.delete')->only(['destroy']);
        $this->middleware('permission:service-sale-return.approve')->only(['status']);
        $this->middleware('permission:service-sale-return.print')->only(['print']);

        $this->service_sale_return_service = $service_sale_return_service;
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

        return view('admin.service_sale_return.index', compact('business', 'customers', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->service_sale_return_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $service_sales = $this->service_sale_return_service->getEligibleServiceSales();
        $service_sale_return_no = generateServiceSaleReturnNo();

        return view('admin.service_sale_return.create', compact('business', 'service_sales', 'service_sale_return_no'));
    }

    public function edit($service_sale_return_id)
    {
        $service_sale_return = $this->service_sale_return_service->getById($service_sale_return_id);

        if (!$service_sale_return || $service_sale_return->status !== Status::PENDING) {
            return redirect('admin/service-sale-return')
                ->with('error', 'Only pending service sale returns can be edited.');
        }

        $service_sale_return_details = $this->service_sale_return_service->getDetails($service_sale_return_id);
        $business = $this->business_service->getAll();
        $service_sales = $this->service_sale_return_service->getEligibleServiceSales();

        if ($service_sale_return->serviceSale && !$service_sales->contains('service_sale_id', $service_sale_return->service_sale_id)) {
            $service_sales->push($service_sale_return->serviceSale);
        }

        return view('admin.service_sale_return.create', compact('service_sale_return', 'service_sale_return_details', 'business', 'service_sales'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_sale_id' => ['required', Rule::exists('service_sales', 'service_sale_id')->where('is_deleted', 0)],
            'service_sale_return_no' => [
                'required',
                Rule::unique('service_sale_returns', 'service_sale_return_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->service_sale_return_id, 'service_sale_return_id'),
            ],
            'service_sale_return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_sale_detail_id' => ['required', Rule::exists('service_sale_details', 'service_sale_detail_id')],
            'items.*.return_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['service_sale_return_date'] = ($request->service_sale_return_id)
                ? utcDate($request->service_sale_return_date, true)
                : utcDate($request->service_sale_return_date);

            $this->service_sale_return_service->save($obj);

            return redirect('admin/service-sale-return')
                ->with('success', empty($request->service_sale_return_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'service_sale_return_id' => 'required|exists:service_sale_returns,service_sale_return_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->service_sale_return_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($service_sale_return_id)
    {
        try {
            $this->service_sale_return_service->delete($service_sale_return_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($service_sale_return_id)
    {
        try {
            $service_sale_return = $this->service_sale_return_service->getDetails($service_sale_return_id);
            return $this->success(Message::SUCCESS, $service_sale_return);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function sourceLines($service_sale_id)
    {
        try {
            $data = $this->service_sale_return_service->getSourceLines($service_sale_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($service_sale_return_id)
    {
        $service_sale_return = $this->service_sale_return_service->getById($service_sale_return_id);

        if (!$service_sale_return) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $service_sale_return->business_id,
                'service_sale_return',
                $service_sale_return_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.service_sale_return.print.print', compact('service_sale_return'));
    }
}
