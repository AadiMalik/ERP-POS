<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\PaymentGatewayService;
use App\Services\PaymentGateways\PaymentGatewayProviderRegistry;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentGatewayController extends Controller
{
    use ResponseAPI;

    protected $payment_gateway_service;
    protected $business_service;

    public function __construct(PaymentGatewayService $payment_gateway_service, BusinessService $business_service)
    {
        $this->middleware('permission:payment-gateway.view')->only(['index', 'getData']);
        $this->middleware('permission:payment-gateway.create')->only(['create']);
        $this->middleware('permission:payment-gateway.create|payment-gateway.edit')->only(['store']);
        $this->middleware('permission:payment-gateway.edit')->only(['edit']);
        $this->middleware('permission:payment-gateway.delete')->only(['destroy']);
        $this->middleware('permission:payment-gateway.status')->only(['status']);
        $this->middleware('permission:payment-gateway.test')->only(['testConnection']);
        $this->middleware('module:payment-gateway');

        $this->payment_gateway_service = $payment_gateway_service;
        $this->business_service = $business_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        return view('admin.payment-gateway.index', compact('business'));
    }

    public function getData(Request $request)
    {
        return $this->payment_gateway_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $providers = PaymentGatewayProviderRegistry::forSelect();
        return view('admin.payment-gateway.create', compact('business', 'providers'));
    }

    public function edit($payment_gateway_id)
    {
        $gateway = $this->payment_gateway_service->getById($payment_gateway_id);
        $business = $this->business_service->getAll();
        $providers = PaymentGatewayProviderRegistry::forSelect();
        $provider = PaymentGatewayProviderRegistry::find($gateway->provider_code);
        $masked_sandbox = $gateway->maskedConfig('sandbox');
        $masked_live = $gateway->maskedConfig('live');

        return view('admin.payment-gateway.create', compact('gateway', 'business', 'providers', 'provider', 'masked_sandbox', 'masked_live'));
    }

    public function store(Request $request)
    {
        $rules = [
            'display_name' => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'country' => 'nullable|string|max:100',
            'active_mode' => 'required|in:sandbox,live',
        ];

        if (empty($request->payment_gateway_id)) {
            $rules['provider_code'] = 'required|string';
        }

        if (getRoleName() == RoleNames::SUPERADMIN) {
            $rules['business_id'] = 'required|exists:businesses,business_id';
        }

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return redirect()->back()->withErrors($validate)->withInput();
        }

        $obj = $request->only([
            'payment_gateway_id',
            'provider_code',
            'display_name',
            'description',
            'country',
            'active_mode',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['website_enabled'] = $request->has('website_enabled');
        $obj['mobile_enabled'] = $request->has('mobile_enabled');
        $obj['sort_order'] = (int) ($request->sort_order ?? 0);
        $obj['supported_currencies'] = array_values(array_filter(array_map('trim', explode(',', $request->supported_currencies ?? ''))));
        $obj['supported_payment_methods'] = $request->input('supported_payment_methods', []);
        $obj['config_sandbox'] = $request->input('config_sandbox', []);
        $obj['config_live'] = $request->input('config_live', []);

        try {
            $this->payment_gateway_service->save($obj);
        } catch (Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect('admin/payment-gateway')
            ->with('success', empty($request->payment_gateway_id) ? Message::SAVE : Message::UPDATE);
    }

    public function status($payment_gateway_id)
    {
        try {
            $this->payment_gateway_service->status($payment_gateway_id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($payment_gateway_id)
    {
        try {
            $this->payment_gateway_service->delete($payment_gateway_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function testConnection($payment_gateway_id)
    {
        try {
            $result = $this->payment_gateway_service->testConnection($payment_gateway_id);
            return $result['success']
                ? $this->success($result['message'], [])
                : $this->error($result['message']);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
