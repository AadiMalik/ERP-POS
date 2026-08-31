<?php

namespace App\Http\Controllers\Api\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Intro\IntroPublicService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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
        if (filled($request->input('website'))) {
            return $this->success(Message::SAVE, null);
        }

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
            'payment_reference' => 'nullable|string|max:150',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $data = $request->except(['payment_proof']);

            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');
                $fileName = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
                $path = public_path('uploads/subscription_payments');
                if (!File::exists($path)) {
                    File::makeDirectory($path, 0755, true);
                }
                $file->move($path, $fileName);
                $data['payment_proof'] = $fileName;
            }

            $row = $this->service->registerBusiness($data);
            return $this->success(Message::SAVE, $row->load(['business', 'package']));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}