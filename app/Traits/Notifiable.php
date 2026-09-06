<?php

namespace App\Traits;

use App\Services\Concrete\Admin\NotificationDispatchService;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Explicit, opt-in in-app alert dispatch for Service classes - called from
 * the same points a Service already calls App\Traits\Auditable::logActivity(),
 * e.g. right after an OrderStatusHistory row is written. Not an Eloquent
 * observer, matching the rest of the codebase's explicit-at-the-Service-layer
 * convention (see Auditable).
 */
trait Notifiable
{
    protected function notify(
        string $type,
        ?string $businessId,
        ?string $branchId,
        string $title,
        string $message,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $url = null,
        ?array $data = null,
        ?string $dedupeKey = null,
        ?array $roles = null,
        ?array $explicitUserIds = null
    ) {
        try {
            app(NotificationDispatchService::class)->dispatch(
                $type,
                $businessId ?? Auth::user()?->business_id,
                $branchId ?? Auth::user()?->branch_id,
                $title,
                $message,
                $referenceType,
                $referenceId,
                $url,
                $data,
                // Default dedupe key makes each call unique (order status
                // changes are event-driven, not periodic) - callers that
                // need periodic/threshold dedupe pass an explicit key.
                $dedupeKey ?? generateUuid(),
                $roles,
                $explicitUserIds
            );
        } catch (Throwable $e) {
            // Notification dispatch must never break the primary transaction
            // it is attached to, mirroring Auditable::logActivity().
            report($e);
        }
    }
}
