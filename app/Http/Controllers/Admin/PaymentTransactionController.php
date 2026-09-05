<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PaymentTransactionService;
use App\Services\PaymentGateways\PaymentGatewayProviderRegistry;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class PaymentTransactionController extends Controller
{
    use ResponseAPI;

    protected $payment_transaction_service;
    protected $business_service;

    public function __construct(PaymentTransactionService $payment_transaction_service, BusinessService $business_service)
    {
        $this->middleware('permission:payment-transaction.view')->only(['index', 'getData', 'show']);
        $this->middleware('permission:payment-transaction.refund')->only(['refund']);
        $this->middleware('module:payment-gateway');

        $this->payment_transaction_service = $payment_transaction_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $providers = PaymentGatewayProviderRegistry::providers();
        return view('admin.payment-transaction.index', compact('business', 'providers'));
    }

    public function getData(Request $request)
    {
        return $this->payment_transaction_service->getData($request->all());
    }

    public function show($payment_transaction_id)
    {
        $transaction = $this->payment_transaction_service->getById($payment_transaction_id);
        return view('admin.payment-transaction.show', compact('transaction'));
    }

    public function refund(Request $request, $payment_transaction_id)
    {
        try {
            $this->payment_transaction_service->refundTransaction($payment_transaction_id, $request->amount ? (float) $request->amount : null);
            return $this->success(Message::SUCCESS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
