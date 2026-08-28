<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(IntroPublicService $service)
    {
        $this->service = $service;
    }

    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'package_id' => 'required|exists:packages,package_id',
            'billing_cycle' => 'nullable|in:monthly,yearly',
            'business_name' => 'required|string|max:150',
            'owner_name' => 'required|string|max:150',
            'owner_email' => 'required|email|max:150',
            'owner_phone' => 'nullable|string|max:50',
            'business_email' => 'nullable|email|max:150',
            'business_phone' => 'nullable|string|max:50',
            'business_type' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $row = $this->service->registerBusiness($request->all());
            return $this->success(Message::SAVE, $row->load(['business', 'package']));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}