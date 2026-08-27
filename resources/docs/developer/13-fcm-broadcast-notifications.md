# FCM Broadcast Notifications (Developer)

## Overview

Business-scoped Firebase Cloud Messaging for admin marketing broadcasts.
Transactional order pushes are **not** implemented yet but should call
`App\Services\Concrete\Firebase\FirebaseNotificationService`.

No `kreait/firebase-php` package — PHP 8.0 / Laravel 9 uses Guzzle HTTP v1 +
service-account JWT OAuth (`FirebaseNotificationService`).

## Tables

| Table | Purpose |
|---|---|
| `user_fcm_tokens` | Multi-device tokens per `user_id` + `business_id`; `is_active` gating |
| `firebase_settings` | One row per business; `private_key` encrypted cast |
| `notification_templates` | Reusable payloads (`status` active/inactive, soft delete) |
| `broadcast_notifications` | Campaign snapshot + counters + lifecycle status |
| `broadcast_notification_recipients` | Per-token snapshot + send outcome |

## Statuses

**Campaign:** `draft` → `queued` → `processing` → `completed` \| `cancelled` \| `failed`

**Recipient:** `pending` → `sending` → `sent` \| `failed` \| `cancelled`

## Flow

1. Admin creates campaign (`BroadcastNotificationService::createCampaign`) — draft + recipient rows for each **active** token of selected users.
2. **Start** validates Firebase config (`FirebaseSettingService::hasValidConfiguration`), lockForUpdate status transition to `queued`, dispatches `ProcessBroadcastNotificationJob`.
3. Job (`ShouldBeUnique` per campaign) claims batches of 50 pending → `sending`, calls `FirebaseNotificationService::sendToToken`, updates recipient + counters, re-dispatches until done or cancelled.
4. Permanent FCM token errors (`UNREGISTERED`, etc.) deactivate `user_fcm_tokens.is_active`.
5. **Cancel** marks pending/sending recipients cancelled; job exits on cancelled status.
6. **Resend Failed** resets failed rows whose token is still active to `pending` and re-queues.

## Permissions

| Module | Names |
|---|---|
| `firebase-setting` | `firebase-setting.manage` |
| `notification-template` | `notification-template.view\|create\|edit\|delete\|status` |
| `broadcast-notification` | `broadcast-notification.view\|create\|start\|cancel\|resend\|delete` |

Seed with `php artisan db:seed --class=PermissionSeeder`.

## Controllers / Routes

- Firebase config: Settings tab — `POST admin/setting/firebase` (`firebase.update`)
- `NotificationTemplateController` — `admin/notification-template` (+ DataTables)
- `BroadcastNotificationController` — `admin/broadcast-notification` (+ start/cancel/resend)

Sidebar: **Push Notifications** group for templates/broadcasts; Firebase under **Settings**.

## Future mobile token API

Use `UserFcmTokenService::registerOrUpdate()` from a Sanctum customer endpoint
(not implemented in this task).

## Future transactional notifications

```php
app(FirebaseNotificationService::class)->sendToToken(
    $businessId, $token, $title, $body, $image, $data
);
```

## Ops

- `QUEUE_CONNECTION` should not be `sync` in production for large campaigns.
- `php artisan queue:work`
- Ensure `APP_KEY` is stable — it encrypts `firebase_settings.private_key`.
