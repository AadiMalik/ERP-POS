<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Mobile\MobileCmsService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterSubscriberController extends Controller
{
    use ResponseAPI;

    protected $cms_service;

    public function __construct(MobileCmsService $cms_service)
    {
        $this->cms_service = $cms_service;
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

        $validate = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'source' => 'nullable|string|max:100',
        ]);
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 422);
        }

        $this->cms_service->subscribeNewsletter($business_id, $request->email, $request->source);

        return $this->success(Message::SAVE, ['subscribed' => true]);
    }
}
