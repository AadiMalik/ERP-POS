<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Mobile\MobileCmsService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactMessageController extends Controller
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ]);
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 422);
        }

        $this->cms_service->submitContact(
            $business_id,
            $request->only(['name', 'email', 'phone', 'subject', 'message'])
        );

        return $this->success(Message::SAVE, ['sent' => true]);
    }
}
