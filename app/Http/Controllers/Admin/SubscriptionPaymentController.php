<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\Concrete\Admin\PaymentService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class SubscriptionPaymentController extends Controller
{
    use ResponseAPI;

    protected PaymentService $payment_service;

    public function __construct(PaymentService $payment_service)
    {
        $this->middleware('permission:subscription.manage');

        $this->payment_service = $payment_service;
    }

    public function approve($subscription_payment_id)
    {
        try {
            $payment = SubscriptionPayment::findOrFail($subscription_payment_id);
            $this->payment_service->approve($payment);

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reject(Request $request, $subscription_payment_id)
    {
        try {
            $payment = SubscriptionPayment::findOrFail($subscription_payment_id);
            $this->payment_service->reject($payment, $request->reason ?? 'Rejected by Super Admin');

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
