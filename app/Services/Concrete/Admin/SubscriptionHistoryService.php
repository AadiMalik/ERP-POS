<?php

namespace App\Services\Concrete\Admin;

use App\Models\SubscriptionHistory;

/**
 * Thin append-only audit-trail writer for subscription/billing lifecycle
 * events. Mirrors DocumentSendLogService's shape. This is the single place
 * every mutation in SubscriptionService/InvoiceService/PaymentService logs
 * through, so the audit trail can never drift out of sync with what
 * actually happened.
 */
class SubscriptionHistoryService
{
    public function log(
        string $business_id,
        ?string $business_subscription_id,
        string $event_type,
        ?string $from_status = null,
        ?string $to_status = null,
        ?string $from_package_id = null,
        ?string $to_package_id = null,
        ?string $notes = null,
        ?array $meta = null,
        ?int $createdby_id = null
    ) {
        return SubscriptionHistory::create([
            'subscription_history_id' => generateUuid(),
            'business_id' => $business_id,
            'business_subscription_id' => $business_subscription_id,
            'event_type' => $event_type,
            'from_status' => $from_status,
            'to_status' => $to_status,
            'from_package_id' => $from_package_id,
            'to_package_id' => $to_package_id,
            'notes' => $notes,
            'meta' => $meta,
            'createdby_id' => $createdby_id,
            'date_created' => now(),
        ]);
    }
}
