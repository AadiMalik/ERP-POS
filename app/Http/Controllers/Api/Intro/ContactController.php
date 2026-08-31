<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        // Honeypot: bots that fill the hidden "website" field get a fake
        // success without creating a row (don't tip them off with 422).
        if (filled($request->input('website'))) {
            return $this->success(Message::SAVE, null);
        }

        $validate = Validator::make($request->all(), [
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:50',
            'subject' => 'nullable|string|max:200',
            'message' => 'required|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $row = $this->service->submitContact($request->only(['name', 'email', 'phone', 'subject', 'message']));
            return $this->success(Message::SAVE, $row);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}