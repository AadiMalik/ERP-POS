<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\PaymentMethod;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CustomerPaymentService;
use App\Services\Concrete\Admin\CustomerService;
use App\Services\Concrete\Admin\OrderService;
use App\Services\Concrete\Admin\SettingService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CustomerPaymentController extends Controller
{
    use ResponseAPI;

    protected $customer_payment_service;
    protected $business_service;
    protected $customer_service;
    protected $setting_service;
    protected $account_service;
    protected $order_service;

    public function __construct(
        CustomerPaymentService $customer_payment_service,
        BusinessService $business_service,
        CustomerService $customer_service,
        SettingService $setting_service,
        AccountService $account_service,
        OrderService $order_service
    ) {
        $this->middleware('permission:customer-payment.view')->only(['index', 'getData', 'details', 'customerLedger', 'ordersByCustomer', 'serviceSalesByCustomer']);
        $this->middleware('permission:customer-payment.create')->only(['create']);
        $this->middleware('permission:customer-payment.create|customer-payment.edit')->only(['store']);
        $this->middleware('permission:customer-payment.create')->only(['quickReceive']);
        $this->middleware('permission:customer-payment.edit')->only(['edit']);
        $this->middleware('permission:customer-payment.delete')->only(['destroy']);
        $this->middleware('permission:customer-payment.status')->only(['status']);
        $this->middleware('permission:customer-payment.print')->only(['print']);

        $this->customer_payment_service = $customer_payment_service;
        $this->business_service = $business_service;
        $this->customer_service = $customer_service;
        $this->setting_service = $setting_service;
        $this->account_service = $account_service;
        $this->order_service = $order_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $customers = $this->customer_service->getAllActive();
        $statuses = [
            Status::PENDING => ucfirst(Status::PENDING),
            Status::POSTED  => ucfirst(Status::POSTED),
        ];

        return view('admin.customer_payment.index', compact('business', 'customers', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->customer_payment_service->getData($request->all());
    }

    public function create(Request $request)
    {
        // Lets the Order Details page's "Add Payment" button land here with the
        // customer + order already selected, without duplicating any of
        // CustomerPaymentService::save()'s due-amount logic.
        $prefill_order = $request->filled('order_id')
            ? Order::where('order_id', $request->query('order_id'))->where('is_deleted', 0)->first()
            : null;

        // A Super Admin has no business_id of their own - when landing here via
        // a prefilled order, scope the customer/account dropdowns to that
        // order's business (not Auth::user()->business_id) so the customer is
        // actually selectable.
        $business_id = $prefill_order->business_id ?? Auth::user()->business_id;

        $business = $this->business_service->getAll();
        $customers = $this->customer_service->getAllActive($business_id);
        $accounting_setting = $this->setting_service->getAccountingSetting($business_id);
        $accounts = $this->account_service->getChildByBusiness($business_id);
        $payment_no = generateCustomerPaymentNo();

        return view('admin.customer_payment.create', compact('business', 'customers', 'accounting_setting', 'accounts', 'payment_no', 'prefill_order'));
    }

    public function edit($customer_payment_id)
    {
        $customer_payment = $this->customer_payment_service->getById($customer_payment_id);

        if (!$customer_payment) {
            return redirect('admin/customer-payment')->with('error', Message::NOTFOUND);
        }

        $business = $this->business_service->getAll();
        $customers = $this->customer_service->getAllActive();
        $accounting_setting = $this->setting_service->getAccountingSetting($customer_payment->business_id);
        $accounts = $this->account_service->getChildByBusiness($customer_payment->business_id);

        return view('admin.customer_payment.create', compact('customer_payment', 'business', 'customers', 'accounting_setting', 'accounts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'        => ['required', Rule::exists('users', 'id')->where('is_deleted', 0)],
            'order_id'       => ['nullable', Rule::exists('orders', 'order_id')->where('is_deleted', 0)],
            'service_sale_id' => ['nullable', Rule::exists('service_sales', 'service_sale_id')->where('is_deleted', 0)],
            'payment_date'   => ['required', 'date'],
            'payment_method' => ['required', 'in:' . PaymentMethod::CASH . ',' . PaymentMethod::BANK_TRANSFER . ',' . PaymentMethod::CHEQUE . ',' . PaymentMethod::ONLINE . ',' . PaymentMethod::CARD],
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
            $obj['payment_date'] = ($request->customer_payment_id) ? utcDate($request->payment_date, true) : utcDate($request->payment_date);
            $obj['cheque_date'] = $request->cheque_date ? (($request->customer_payment_id) ? utcDate($request->cheque_date, true) : utcDate($request->cheque_date)) : null;
            $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
            $obj['branch_id'] = $request->branch_id ?? Auth::user()->branch_id ?? null;

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $path = public_path('uploads/customer_payment');
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }
                $file->move($path, $fileName);
                $obj['attachment'] = $fileName;
            }

            $this->customer_payment_service->save($obj);

            return redirect('admin/customer-payment')
                ->with('success', empty($request->customer_payment_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /**
     * Quick "Receive Payment" against a specific order - used by the POS
     * Order History detail modal (and reusable anywhere else a one-tap
     * payment capture is needed) instead of the full Customer Payment
     * create page. Creates the CustomerPayment via the existing save() then
     * immediately posts it via the existing status() - both untouched, so
     * overpay rejection, JV posting, and order.paid_amount bookkeeping are
     * all inherited rather than re-implemented here.
     */
    public function quickReceive(Request $request)
    {
        $rules = [
            'order_id'       => ['required', Rule::exists('orders', 'order_id')->where('is_deleted', 0)],
            'amount'         => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:' . PaymentMethod::CASH . ',' . PaymentMethod::BANK_TRANSFER . ',' . PaymentMethod::CHEQUE . ',' . PaymentMethod::ONLINE . ',' . PaymentMethod::CARD],
            'reference_no'   => ['nullable', 'string', 'max:191'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $order = Order::where('order_id', $request->order_id)->where('is_deleted', 0)->firstOrFail();

            if ($order->status === 'cancelled') {
                throw new Exception('Cannot receive payment for a cancelled order.');
            }

            if (empty($order->user_id)) {
                throw new Exception('This order has no customer to receive payment from.');
            }

            $money_scale = (int) (session('accounting_setting.decimal_points') ?? 2);
            $due = round((float) $order->total - (float) $order->paid_amount, $money_scale);

            if ($due <= 0) {
                throw new Exception('This order is already fully paid.');
            }

            $payment = $this->customer_payment_service->save([
                'business_id'      => $order->business_id,
                'branch_id'        => $order->branch_id,
                'user_id'          => $order->user_id,
                'order_id'         => $order->order_id,
                'payment_date'     => now(),
                'payment_method'   => $request->payment_method,
                'reference_no'     => $request->reference_no,
                'amount'           => $request->amount,
                'tax_amount'       => 0,
                'discount_amount'  => 0,
                'remarks'          => 'Receive Payment',
            ]);

            $this->customer_payment_service->status([
                'customer_payment_id' => $payment->customer_payment_id,
                'status'               => Status::POSTED,
            ]);

            $details = $this->order_service->getDetails($order->order_id);

            return $this->success(Message::SAVE, $details);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'customer_payment_id' => 'required|exists:customer_payments,customer_payment_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::POSTED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->customer_payment_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($customer_payment_id)
    {
        try {
            $this->customer_payment_service->delete($customer_payment_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($customer_payment_id)
    {
        try {
            $customer_payment = $this->customer_payment_service->getDetails($customer_payment_id);
            return $this->success(Message::SUCCESS, $customer_payment);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function customerLedger($user_id)
    {
        try {
            $ledger = $this->customer_service->getCustomerLedger($user_id);

            return $this->success(Message::SUCCESS, $ledger);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function ordersByCustomer($user_id)
    {
        try {
            $money_scale = (int) (session('accounting_setting.decimal_points') ?? 2);
            $orders = Order::where('user_id', $user_id)
                ->where('status', '!=', 'cancelled')
                ->where('is_deleted', 0)
                ->get(['order_id', 'daily_order_id', 'order_date', 'total', 'paid_amount'])
                ->map(function ($order) use ($money_scale) {
                    $order->due_amount = round((float) $order->total - (float) $order->paid_amount, $money_scale);
                    $order->order_date = $order->order_date ? localDate($order->order_date) : null;
                    return $order;
                })
                ->filter(fn ($order) => $order->due_amount > 0)
                ->values();

            return $this->success(Message::SUCCESS, $orders);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function serviceSalesByCustomer($user_id)
    {
        try {
            $service_sales = \App\Models\ServiceSale::where('customer_id', $user_id)
                ->where('status', Status::APPROVED)
                ->where('is_deleted', 0)
                ->get(['service_sale_id', 'service_sale_no', 'total']);

            return $this->success(Message::SUCCESS, $service_sales);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function print($customer_payment_id)
    {
        $payment = $this->customer_payment_service->getById($customer_payment_id);

        if (!$payment) {
            abort(404);
        }

        return view('admin.customer_payment.print.print', compact('payment'));
    }
}
