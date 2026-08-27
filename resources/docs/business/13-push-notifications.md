# Push Notifications (FCM Broadcasts)

Admins can send marketing push notifications to customers' mobile devices using
Firebase Cloud Messaging (FCM), configured **per business**.

## What You Can Do

1. **Firebase Settings** — Under **Settings → Firebase**, enter your Firebase project ID,
   service-account client email, and private key for this business. Turn the
   configuration **Active** before sending anything.
2. **Notification Templates** — Save reusable title/body/image/data payloads.
   Activate or deactivate templates as needed.
3. **Broadcast Notifications** — Create a campaign (optionally from a template),
   pick customers who currently have at least one active device token, save as
   **Draft**, then **Start** when ready.

## Campaign Actions

| Action | When available |
|---|---|
| **Start** | Draft (or after a full failure) — only if Firebase is configured and pending recipients exist |
| **Cancel** | Queued or processing — already-sent messages stay sent; remaining pending become cancelled |
| **Resend Failed** | After completion/cancel when some recipients failed — only retries tokens that are still active |

## Important Rules

- Notifications are sent in the background (queue). Large audiences are processed
  in batches; you can watch success/failed/pending counts on the campaign detail page.
- Editing a template later does **not** change campaigns already created (content is copied).
- Invalid/expired device tokens are automatically marked inactive so future
  campaigns skip them.
- Each business only sees and can send to its own customers and Firebase credentials.

## Prerequisites

- A Firebase project with Cloud Messaging enabled and a service account key.
- Customers must have registered device tokens (via the mobile app — not part of
  the admin screens). Until tokens exist, the recipient list on Create Broadcast
  will be empty.
- The server queue worker must be running (`php artisan queue:work`) for sends to process.
