<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\PaymentMethod;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\SettingService;
use App\Services\Concrete\Admin\SupplierPaymentService;
use App\Services\Concrete\Admin\SupplierService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SupplierPaymentController extends Controller
{
    use ResponseAPI;

    protected $supplier_payment_service;
    protected $business_service;
    protected $supplier_service;
    protected $setting_service;
    protected $account_service;
    protected $document_send_log_service;

    public function __construct(
        SupplierPaymentService $supplier_payment_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        SettingService $setting_service,
        AccountService $account_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:supplier-payment.view')->only(['index', 'getData', 'details', 'supplierLedger', 'purchasesBySupplier']);
        $this->middleware('permission:supplier-payment.create')->only(['create']);
        $this->middleware('permission:supplier-payment.create|supplier-payment.edit')->only(['store']);
        $this->middleware('permission:supplier-payment.edit')->only(['edit']);
        $this->middleware('permission:supplier-payment.delete')->only(['destroy']);
        $this->middleware('permission:supplier-payment.status')->only(['status']);
        $this->middleware('permission:supplier-payment.print')->only(['print']);
        $this->middleware('module:supplier-payment');

        $this->supplier_payment_service = $supplier_payment_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->setting_service = $setting_service;
        $this->account_service = $account_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $statuses = [
            Status::PENDING => ucfirst(Status::PENDING),
            Status::POSTED  => ucfirst(Status::POSTED),
        ];

        return view('admin.supplier_payment.index', compact('business', 'suppliers', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->supplier_payment_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $accounting_setting = $this->setting_service->getAccountingSetting(Auth::user()->business_id);
        $accounts = $this->account_service->getChildByBusiness(Auth::user()->business_id);
        $payment_no = generateSupplierPaymentNo();

        return view('admin.supplier_payment.create', compact('business', 'suppliers', 'accounting_setting', 'accounts', 'payment_no'));
    }

    public function edit($supplier_payment_id)
    {
        $supplier_payment = $this->supplier_payment_service->getById($supplier_payment_id);

        if (!$supplier_payment) {
            return redirect('admin/supplier-payment')
                ->with('error', Message::NOTFOUND);
        }

        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $accounting_setting = $this->setting_service->getAccountingSetting($supplier_payment->business_id);
        $accounts = $this->account_service->getChildByBusiness($supplier_payment->business_id);

        return view('admin.supplier_payment.create', compact('supplier_payment', 'business', 'suppliers', 'accounting_setting', 'accounts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'supplier_id'    => ['required', Rule::exists('suppliers', 'supplier_id')->where('is_deleted', 0)],
            'purchase_id'    => ['nullable', Rule::exists('purchases', 'purchase_id')->where('is_deleted', 0)],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'in:' . PaymentMethod::CASH . ',' . PaymentMethod::BANK_TRANSFER . ',' . PaymentMethod::CHEQUE . ',' . PaymentMethod::ONLINE],
            'payment_account_id' => ['nullable', Rule::exists('accounts', 'account_id')->where('is_deleted', 0)],
            'reference_no'   => ['nullable', 'string', 'max:191'],
            'cheque_date'    => ['nullable', 'date'],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'tax_amount'     => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks'        => ['nullable', 'string'],
            'attachment'     => ['nullable', 'file', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['payment_date'] = ($request->supplier_payment_id) ? utcDate($request->payment_date, true) : utcDate($request->payment_date);
            $obj['cheque_date'] = $request->cheque_date ? (($request->supplier_payment_id) ? utcDate($request->cheque_date, true) : utcDate($request->cheque_date)) : null;
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $path = public_path('uploads/supplier_payment');
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }
                $file->move($path, $fileName);
                $obj['attachment'] = $fileName;
            }

            $this->supplier_payment_service->save($obj);

            return redirect('admin/supplier-payment')
                ->with('success', empty($request->supplier_payment_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'supplier_payment_id' => 'required|exists:supplier_payments,supplier_payment_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::POSTED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->supplier_payment_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($supplier_payment_id)
    {
        try {
            $this->supplier_payment_service->delete($supplier_payment_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($supplier_payment_id)
    {
        try {
            $supplier_payment = $this->supplier_payment_service->getDetails($supplier_payment_id);
            return $this->success(Message::SUCCESS, $supplier_payment);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function supplierLedger($supplier_id)
    {
        try {
            $supplier = $this->supplier_service->getById($supplier_id);

            if (!$supplier) {
                return $this->error(Message::NOTFOUND);
            }

            $ledger = $this->supplier_payment_service->getSupplierLedger($supplier_id, $supplier->business_id);

            return $this->success(Message::SUCCESS, [
                'balance'     => $ledger['balance'],
                'type'        => $ledger['type'],
                'account_id'  => $supplier->account_id,
                'account_code' => $supplier->account->code ?? '',
                'account_name' => $supplier->account->name ?? '',
            ]);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function purchasesBySupplier($supplier_id)
    {
        try {
            $purchases = Purchase::where('supplier_id', $supplier_id)
                ->whereIn('status', [Status::APPROVED, Status::COMPLETED])
                ->where('is_deleted', 0)
                ->get(['purchase_id', 'purchase_no', 'total']);

            return $this->success(Message::SUCCESS, $purchases);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function print($supplier_payment_id)
    {
        $payment = $this->supplier_payment_service->getById($supplier_payment_id);

        if (!$payment) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $payment->business_id,
                'supplier_payment',
                $supplier_payment_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.supplier_payment.print.print', compact('payment'));
    }
}
