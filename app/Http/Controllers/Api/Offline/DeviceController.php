<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Offline\OfflineDeviceService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    use ResponseAPI;

    protected $device_service;

    public function __construct(OfflineDeviceService $device_service)
    {
        $this->device_service = $device_service;
    }

    public function register(Request $request)
    {
        $validate = Validator::make($request->all(), [
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
            $result = $this->device_service->register(Auth::user(), $request->all());

            return $this->success('Device registered.', $result);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function info(Request $request)
    {
        $device = $request->attributes->get('pos_device');

        if (!$device) {
            return $this->error('Device not found.', 404);
        }

        return $this->success('Device info.', $this->device_service->formatDevice($device));
    }
}
