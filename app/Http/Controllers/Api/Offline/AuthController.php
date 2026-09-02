<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Offline\OfflineAuthService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ResponseAPI;

    protected $auth_service;

    public function __construct(OfflineAuthService $auth_service)
    {
        $this->auth_service = $auth_service;
    }

    public function login(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'business_id' => ['required', 'string'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->auth_service->login(
                $request->email,
                $request->password,
                $request->business_id
            );

            return $this->success('Login successful.', $result);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function ping()
    {
        return $this->success('Offline POS API is reachable.', [
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
