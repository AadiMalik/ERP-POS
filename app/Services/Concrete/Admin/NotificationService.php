<?php

namespace App\Services\Concrete\Admin;

use App\Models\NotificationRecipient;
use Carbon\Carbon;
use Yajra\DataTables\DataTables;

/**
 * Read-side of the notification system - the personal inbox for the
 * currently authenticated user (bell dropdown + Notifications page).
 * Dispatch/creation lives in NotificationDispatchService.
 */
class NotificationService
{
    public function getData($obj)
    {
        $datatable = NotificationRecipient::with('notification')
            ->where('user_id', $obj['user_id']);

        if (!empty($obj['type'])) {
            $datatable->whereHas('notification', function ($q) use ($obj) {
                $q->where('type', $obj['type']);
            });
        }

        if (isset($obj['is_read']) && $obj['is_read'] !== '' && $obj['is_read'] !== null) {
            $datatable->where('is_read', (int) $obj['is_read']);
        }

        if (!empty($obj['start_date'])) {
            $datatable->where('date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay());
        }

        if (!empty($obj['end_date'])) {
            $datatable->where('date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay());
        }

        $datatable->orderBy('date_created', 'desc');

        return DataTables::of($datatable)
            ->addColumn('title', function ($item) {
                return $item->notification->title ?? '';
            })
            ->addColumn('message', function ($item) {
                return $item->notification->message ?? '';
            })
            ->addColumn('type', function ($item) {
                return ucwords(str_replace('_', ' ', $item->notification->type ?? ''));
            })
            ->addColumn('date_created', function ($item) {
                return !empty($item->notification->date_created) ? localDateTime($item->notification->date_created) : 'N/A';
            })
            ->addColumn('status', function ($item) {
                return $item->is_read
                    ? '<span class="badge bg-label-secondary">Read</span>'
                    : '<span class="badge bg-label-primary">Unread</span>';
            })
            ->addColumn('url', function ($item) {
                return $item->notification->url ?? '';
            })
            ->rawColumns(['status'])
            ->make(true);
    }

    public function unreadCount($user_id, ?string $type = null)
    {
        $query = NotificationRecipient::where('user_id', $user_id)->where('is_read', 0);

        if ($type) {
            $query->whereHas('notification', fn ($q) => $q->where('type', $type));
        }

        return $query->count();
    }

    public function latest($user_id, $limit = 10, ?string $type = null)
    {
        $query = NotificationRecipient::with('notification')
            ->where('user_id', $user_id);

        if ($type) {
            $query->whereHas('notification', fn ($q) => $q->where('type', $type));
        }

        return $query
            ->orderBy('date_created', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($recipient) {
                return [
                    'id'      => $recipient->notification_recipient_id,
                    'title'   => $recipient->notification->title ?? '',
                    'message' => $recipient->notification->message ?? '',
                    'type'    => $recipient->notification->type ?? '',
                    'url'     => $recipient->notification->url ?? '#',
                    'is_read' => (bool) $recipient->is_read,
                    'date'    => !empty($recipient->notification->date_created)
                        ? Carbon::parse($recipient->notification->date_created)->diffForHumans()
                        : '',
                ];
            });
    }

    public function markRead($notification_recipient_id, $user_id)
    {
        $recipient = NotificationRecipient::where('notification_recipient_id', $notification_recipient_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$recipient) {
            return false;
        }

        if (!$recipient->is_read) {
            $recipient->update(['is_read' => 1, 'read_at' => now()]);
        }

        return true;
    }

    public function markAllRead($user_id)
    {
        return NotificationRecipient::where('user_id', $user_id)
            ->where('is_read', 0)
            ->update(['is_read' => 1, 'read_at' => now()]);
    }
}
