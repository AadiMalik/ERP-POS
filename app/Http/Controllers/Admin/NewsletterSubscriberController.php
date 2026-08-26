<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\NewsletterSubscriberService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class NewsletterSubscriberController extends Controller
{
    use ResponseAPI;

    protected $subscriber_service;

    public function __construct(NewsletterSubscriberService $subscriber_service)
    {
        $this->middleware('permission:newsletter-subscriber.view')->only(['index', 'getData']);
        $this->middleware('permission:newsletter-subscriber.status')->only(['status']);
        $this->middleware('permission:newsletter-subscriber.delete')->only(['destroy']);

        $this->subscriber_service = $subscriber_service;
    }

    public function index()
    {
        return view('admin.newsletter_subscriber.index');
    }

    public function getData(Request $request)
    {
        return $this->subscriber_service->getData($request->all());
    }

    public function status($id)
    {
        try {
            $this->subscriber_service->status($id);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $this->subscriber_service->delete($id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
