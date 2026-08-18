<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Database\QueryException;

/**
 * Single place that creates a Notification + resolves/creates its
 * NotificationRecipient rows. Not a queued job: called synchronously (via
 * App\Traits\Notifiable) from Service classes for event-driven alerts, and
 * from App\Console\Commands\CheckNotificationAlertsCommand for periodic/
 * threshold alerts - mirrors how App\Traits\Auditable writes ActivityLog
 * rows explicitly rather than through model events.
 */
class NotificationDispatchService
{
    public function dispatch(
        string $type,
        ?string $businessId,
        ?string $branchId,
        string $title,
        string $message,
        ?string $referenceType,
        ?string $referenceId,
        ?string $url,
        ?array $data,
        string $dedupeKey,
        ?array $roles = null
    ): ?Notification {
        try {
            $notification = Notification::create([
                'notification_id' => generateUuid(),
                'business_id'     => $businessId,
                'branch_id'       => $branchId,
                'type'            => $type,
                'title'           => $title,
                'message'         => $message,
                'reference_type'  => $referenceType,
                'reference_id'    => $referenceId,
                'url'             => $url,
                'data'            => $data,
                'dedupe_key'      => $dedupeKey,
                'date_created'    => now(),
            ]);
        } catch (QueryException $e) {
            // Unique index on (type, reference_type, reference_id, dedupe_key)
            // is the duplicate-send guard - already dispatched for this
            // event/period, skip silently (same pattern as
            // SubscriptionReminderLog in ProcessSubscriptionLifecycleCommand).
            return null;
        }

        foreach ($this->resolveRecipients($businessId, $branchId, $roles ?? $this->defaultRoles($type)) as $userId) {
            NotificationRecipient::create([
                'notification_recipient_id' => generateUuid(),
                'notification_id'           => $notification->notification_id,
                'user_id'                   => $userId,
                'is_read'                   => 0,
                'date_created'               => now(),
            ]);
        }

        return $notification;
    }

    protected function defaultRoles(string $type): array
    {
        // Subscription expiry is dispatched twice by the caller (once per
        // audience, each with its own url/dedupe_key) - see
        // CheckNotificationAlertsCommand::checkSubscriptionExpiry(). Every
        // other alert type reaches Business Admin + Branch Admin.
        return [RoleNames::BUSINESSADMIN, RoleNames::BRANCHADMIN];
    }

    protected function resolveRecipients(?string $businessId, ?string $branchId, array $roles): array
    {
        $userIds = [];

        if (in_array(RoleNames::SUPERADMIN, $roles, true)) {
            $userIds = array_merge($userIds, User::role(RoleNames::SUPERADMIN)->pluck('id')->all());
        }

        if ($businessId && in_array(RoleNames::BUSINESSADMIN, $roles, true)) {
            $userIds = array_merge($userIds, User::role(RoleNames::BUSINESSADMIN)
                ->where('business_id', $businessId)
                ->pluck('id')->all());
        }

        if ($businessId && $branchId && in_array(RoleNames::BRANCHADMIN, $roles, true)) {
            $userIds = array_merge($userIds, User::role(RoleNames::BRANCHADMIN)
                ->where('business_id', $businessId)
                ->where('branch_id', $branchId)
                ->pluck('id')->all());
        }

        return array_values(array_unique($userIds));
    }
}
