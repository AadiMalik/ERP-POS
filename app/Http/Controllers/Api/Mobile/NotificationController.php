<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\NotificationService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Customer-facing notification inbox for the Website/Mobile App (order
 * placed, order status changed, customer credit due) - reuses the same
 * NotificationService/NotificationRecipient the ERP bell uses, since
 * read-state is already scoped to the authenticated user regardless of
 * business. Mirrored by Api\NotificationController for the Website surface.
 */
class NotificationController extends Controller
{
    use ResponseAPI;

    protected NotificationService $notification_service;

    public function __construct(NotificationService $notification_service)
    {
        $this->notification_service = $notification_service;
    }

    public function index(Request $request)
    {
        $limit = (int) $request->input('per_page', 20);

        return $this->success(Message::FETCH, $this->notification_service->latest(Auth::id(), $limit));
    }

    public function unreadCount()
    {
        return $this->success(Message::FETCH, ['count' => $this->notification_service->unreadCount(Auth::id())]);
    }

    public function markRead($id)
    {
        $this->notification_service->markRead($id, Auth::id());

        return $this->success(Message::UPDATE, []);
    }

    public function markAllRead()
    {
        $this->notification_service->markAllRead(Auth::id());

        return $this->success(Message::UPDATE, []);
    }
}
