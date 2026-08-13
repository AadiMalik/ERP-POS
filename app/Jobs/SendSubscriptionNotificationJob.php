<?php

namespace App\Jobs;

use App\Models\BusinessSubscription;
use App\Services\Concrete\Admin\SubscriptionNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches a single subscription notification (expiry reminder or
 * lifecycle event) to a business. Mirrors SendPurchaseRequestQuotationJob's
 * shape - one job per unit of work.
 */
class SendSubscriptionNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;
    public $timeout = 120;

    protected string $business_subscription_id;
    protected string $notification_type;
    protected ?int $threshold_days;
    protected ?string $message;

    public function __construct(
        string $business_subscription_id,
        string $notification_type,
        ?int $threshold_days = null,
        ?string $message = null
    ) {
        $this->business_subscription_id = $business_subscription_id;
        $this->notification_type = $notification_type;
        $this->threshold_days = $threshold_days;
        $this->message = $message;
    }

    public function handle(SubscriptionNotificationService $service)
    {
        $subscription = BusinessSubscription::with(['business', 'package'])->find($this->business_subscription_id);

        if (!$subscription) {
            return;
        }

        try {
            if ($this->notification_type === 'reminder') {
                $service->sendReminder($subscription, $this->threshold_days ?? 0);
            } else {
                $service->notifyEvent($subscription, $this->notification_type, $this->message ?? '');
            }
        } catch (\Throwable $e) {
            Log::error('Subscription notification failed: ' . $e->getMessage());

            throw $e;
        }
    }
}
