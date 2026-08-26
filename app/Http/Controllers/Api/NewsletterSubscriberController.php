<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\NewsletterSubscriberService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterSubscriberController extends Controller
{
    use ResponseAPI;

    protected $subscriber_service;

    public function __construct(NewsletterSubscriberService $subscriber_service)
    {
        $this->subscriber_service = $subscriber_service;
    }

    public function store(Request $request, $business_id)
    {
        $business_validate = Validator::make(
            ['business_id' => $business_id],
            ['business_id' => 'required|string|exists:businesses,business_id']
        );
        if ($business_validate->fails()) {
            return $this->error($business_validate->errors()->first(), 404);
        }

        $rules = [
            'email' => 'required|email|max:255',
            'source' => 'nullable|string|max:100',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 422);
        }

        $this->subscriber_service->subscribe($business_id, $request->email, $request->source);

        return $this->success(Message::SAVE, ['subscribed' => true]);
    }
}
