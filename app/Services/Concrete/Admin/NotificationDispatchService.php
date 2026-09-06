<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\PosRegister;
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
        ?array $roles = null,
        ?array $explicitUserIds = null
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

        // $roles === [] means "no role-based recipients" (explicit-only, e.g.
        // a customer or POS notification) - only $roles === null falls back
        // to the type's default business-role audience.
        $roleRecipients = $roles === [] ? [] : $this->resolveRecipients($businessId, $branchId, $roles ?? $this->defaultRoles($type));
        $recipients = array_values(array_unique(array_merge($roleRecipients, $explicitUserIds ?? [])));

        foreach ($recipients as $userId) {
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
        // Every alert type reaches Business Admin + Branch Admin by default.
        // Super-Admin-only alerts (backup_failed, subscription_expiry) and
        // customer/POS-only alerts (order_placed, customer_credit_due, ...)
        // pass an explicit $roles (possibly []) at the call site instead of
        // relying on this default - see NotificationDispatchService::dispatch().
        return [RoleNames::BUSINESSADMIN, RoleNames::BRANCHADMIN];
    }

    /**
     * "The relevant POS" for a Website/Mobile order in a given branch: the
     * assigned cashier of every active register there, falling back to
     * branch-scoped users holding pos.access when no register has one
     * assigned (no "default register per branch" concept exists).
     */
    public function resolvePosRecipients(string $businessId, string $branchId): array
    {
        $assigned = PosRegister::where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->where('status', 'active')
            ->where('is_deleted', 0)
            ->pluck('assigned_user_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($assigned)) {
            return $assigned;
        }

        return User::permission('pos.access')
            ->where('business_id', $businessId)
            ->where('branch_id', $branchId)
            ->pluck('id')
            ->all();
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
