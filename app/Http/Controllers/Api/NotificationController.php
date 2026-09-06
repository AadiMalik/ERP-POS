<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\NotificationService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Website customer-facing notification inbox - mirrors
 * Api\Mobile\NotificationController (same underlying NotificationService,
 * only the namespace/route surface differs, matching CheckoutController/
 * CustomerOrderController's existing Website/Mobile split).
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
