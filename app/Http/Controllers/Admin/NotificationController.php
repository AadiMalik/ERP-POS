<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\NotificationService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ResponseAPI;

    protected $notification_service;

    public function __construct(NotificationService $notification_service)
    {
        $this->middleware('permission:notification.view');

        $this->notification_service = $notification_service;
    }

    public function index()
    {
        $types = [
            'low_stock'                 => 'Low Stock',
            'out_of_stock'               => 'Out of Stock',
            'new_order'                  => 'New Order',
            'payment_due'                => 'Payment Due',
            'credit_limit'               => 'Credit Limit',
            'supplier_payment_reminder'  => 'Supplier Payment Reminder',
            'order_status'               => 'Order Status',
            'order_placed_pos'           => 'Online Order (POS)',
            'subscription_expiry'        => 'Subscription Expiry',
        ];

        return view('admin.notification.index', compact('types'));
    }

    public function getData(Request $request)
    {
        $obj = $request->all();
        $obj['user_id'] = Auth::id();

        return $this->notification_service->getData($obj);
    }

    public function unreadCount()
    {
        return response()->json(['count' => $this->notification_service->unreadCount(Auth::id())]);
    }

    public function latest()
    {
        return response()->json(['data' => $this->notification_service->latest(Auth::id())]);
    }

    public function markRead(Request $request, $id)
    {
        $this->notification_service->markRead($id, Auth::id());

        return $this->success('Notification marked as read.', null);
    }

    public function markAllRead()
    {
        $this->notification_service->markAllRead(Auth::id());

        return $this->success('All notifications marked as read.', null);
    }
}
