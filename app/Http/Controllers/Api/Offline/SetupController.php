<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Offline\OfflineSetupService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SetupController extends Controller
{
    use ResponseAPI;

    protected $setup_service;

    public function __construct(OfflineSetupService $setup_service)
    {
        $this->setup_service = $setup_service;
    }

    public function validateBusiness(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'business_id' => ['required', 'string'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->setup_service->validateBusiness($request->business_id);

            return $this->success('Business is valid for desktop POS.', $result);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function bootstrapBusiness(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'business_id' => ['required', 'string'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->setup_service->fetchBusinessSetupData($request->business_id);

            return $this->success('Business setup data fetched.', $result);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function registerDeviceSetup(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'name' => ['required', 'string', 'max:191'],
            'business_id' => ['required', 'string'],
            'branch_id' => ['required', 'string'],
            'warehouse_id' => ['required', 'string'],
            'pos_register_id' => ['nullable', 'string'],
            'device_fingerprint' => ['nullable', 'string', 'max:128'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->setup_service->registerDeviceWithCredentials($request->all());

            return $this->success('Device registered.', $result);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 401);
        }
    }

    public function locationOptions(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'business_id' => ['required', 'string'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $result = $this->setup_service->getLocationOptions(Auth::user(), $request->business_id);

            return $this->success('Location options.', $result);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 403);
        }
    }
}
