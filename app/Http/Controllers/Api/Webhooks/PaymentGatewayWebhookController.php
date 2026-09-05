<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\PaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Public webhook/callback endpoint every gateway provider posts to. No
 * Sanctum auth (the caller is the payment provider, not one of our users)
 * and no CSRF (this route is loaded under the 'api' middleware group, which
 * never applies VerifyCsrfToken - see routes/api.php). Security instead
 * comes entirely from each provider adapter's own signature verification
 * (see PaymentService::handleWebhook()).
 *
 * Always returns 200 unless there's no active gateway for this
 * (business_id, provider_code) (a misconfigured or deactivated webhook URL)
 * - a provider's retry policy should never be triggered by something we
 * already fully handled (including "invalid signature", which is logged,
 * not thrown back at the caller).
 */
class PaymentGatewayWebhookController extends Controller
{
    protected $payment_service;

    public function __construct(PaymentService $payment_service)
    {
        $this->payment_service = $payment_service;
    }

    public function handle(Request $request, $business_id, $provider_code)
    {
        try {
            $this->payment_service->handleWebhook($business_id, $provider_code, $request);
        } catch (ModelNotFoundException $e) {
            return response('No active payment gateway for this business/provider', 404);
        } catch (\Throwable $e) {
            Log::error('Payment gateway webhook processing failed', [
                'business_id' => $business_id,
                'provider_code' => $provider_code,
                'error' => $e->getMessage(),
            ]);
        }

        return response('OK', 200);
    }
}
