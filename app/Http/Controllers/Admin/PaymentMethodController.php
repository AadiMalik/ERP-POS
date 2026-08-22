<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PaymentMethodService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentMethodController extends Controller
{
    use ResponseAPI;

    protected $payment_method_service;
    protected $business_service;
    protected $account_service;

    public function __construct(
        PaymentMethodService $payment_method_service,
        BusinessService $business_service,
        AccountService $account_service
    ) {
        $this->middleware('permission:payment-method.view')->only(['index', 'getData']);
        $this->middleware('permission:payment-method.create|payment-method.edit')->only(['store']);
        $this->middleware('permission:payment-method.edit')->only(['edit']);
        $this->middleware('permission:payment-method.delete')->only(['destroy']);
        $this->middleware('permission:payment-method.status')->only(['status']);

        $this->payment_method_service = $payment_method_service;
        $this->business_service = $business_service;
        $this->account_service = $account_service;
    }

    public function index()
    {
        $business = $this->business_service->getAllActive();
        $accounts = $this->account_service->getAllChild();
        return view('admin.payment-method.index', compact('business', 'accounts'));
    }

    public function getData(Request $request)
    {
        return $this->payment_method_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                // "Partial"/"Multi Payment" are computed order-level labels
                // (OrderService::resolvePaymentMethodLabel()), never a real,
                // selectable payment method - reserved so POS never offers
                // them as a tender option.
                if (in_array(strtolower(trim($value)), ['partial', 'multi payment'], true)) {
                    $fail('This name is reserved by the system and cannot be used for a payment method.');
                }
            }],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('payment_methods', 'code')
                    ->where(function ($query) use ($request) {
                        return $query->where('business_id', $request->business_id ?? Auth::user()->business_id)
                            ->where('is_deleted', 0);
                    })
                    ->ignore($request->payment_method_id, 'payment_method_id')
            ],
            'type' => ['required', Rule::in(['cash', 'card', 'bank', 'credit', 'wallet', 'other'])],
            'account_id' => [
                Rule::requiredIf($request->type !== 'credit'),
                'nullable',
                Rule::exists('accounts', 'account_id')->where('is_deleted', 0),
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'payment_method_id',
            'name',
            'code',
            'account_id',
            'type',
            'sort_order',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['is_default'] = $request->boolean('is_default');
        $obj['status'] = $request->status ?? 'active';

        try {
            $payment_method = $this->payment_method_service->save($obj);
            return $this->success(
                empty($request->payment_method_id) ? Message::SAVE : Message::UPDATE,
                $payment_method
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function edit($payment_method_id)
    {
        try {
            $payment_method = $this->payment_method_service->getById($payment_method_id);
            return $this->success(
                Message::FETCH,
                $payment_method
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function status($payment_method_id)
    {
        try {
            $this->payment_method_service->status($payment_method_id);
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

    public function destroy($payment_method_id)
    {
        try {

            $this->payment_method_service->delete($payment_method_id);

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
}
