<?php

namespace App\Http\Controllers\Api;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\ContactMessageService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactMessageController extends Controller
{
    use ResponseAPI;

    protected $message_service;

    public function __construct(ContactMessageService $message_service)
    {
        $this->message_service = $message_service;
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:30',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->error($validate->errors()->first(), 422);
        }

        $this->message_service->submit($business_id, $request->only(['name', 'email', 'phone', 'subject', 'message']));

        return $this->success(Message::SAVE, ['sent' => true]);
    }
}
