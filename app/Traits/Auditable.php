<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Throwable;

/**
 * Explicit, opt-in CRUD/audit logging for Service classes - called from
 * save()/status()/delete() at the same point they DB::commit(), mirroring
 * how OrderStatusHistory/SubscriptionHistory rows are written today. Not an
 * Eloquent observer: the codebase has no model-event/booted() pattern
 * anywhere, so logging stays explicit at the Service layer like everything
 * else here.
 */
trait Auditable
{
    protected function logActivity(
        string $module,
        ?string $recordId,
        string $action,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null,
        ?string $businessId = null,
        ?string $branchId = null
    ) {
        try {
            ActivityLog::create([
                'activity_log_id' => generateUuid(),
                'business_id'     => $businessId ?? Auth::user()?->business_id,
                'branch_id'       => $branchId ?? Auth::user()?->branch_id,
                'causer_id'       => Auth::id(),
                'module'          => $module,
                'record_id'       => $recordId,
                'action'          => $action,
                'description'     => $description,
                'old_values'      => $old,
                'new_values'      => $new,
                'ip_address'      => Request::ip(),
                'user_agent'      => Request::userAgent(),
                'date_created'    => now(),
            ]);
        } catch (Throwable $e) {
            // Audit logging must never break the primary transaction it is
            // attached to (e.g. CLI/queue contexts with no request, or a
            // logging failure unrelated to the business operation itself).
            report($e);
        }
    }
}
