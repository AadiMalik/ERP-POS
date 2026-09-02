<?php

namespace App\Http\Controllers\Api\Offline;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Api\Offline\OfflinePushService;
use App\Services\Concrete\Api\Offline\OfflineSyncService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SyncController extends Controller
{
    use ResponseAPI;

    protected $sync_service;
    protected $push_service;

    public function __construct(OfflineSyncService $sync_service, OfflinePushService $push_service)
    {
        $this->sync_service = $sync_service;
        $this->push_service = $push_service;
    }

    public function bootstrap(Request $request)
    {
        try {
            $device = $request->attributes->get('pos_device');
            $data = $this->sync_service->bootstrap($device, $request->query('warehouse_id'));

            return $this->success('Bootstrap sync complete.', $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function pull(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'cursors' => ['nullable', 'array'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $device = $request->attributes->get('pos_device');
            $data = $this->sync_service->pull($device, $request->input('cursors', []), $request->input('warehouse_id'));

            return $this->success('Incremental sync complete.', $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function push(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'transactions' => ['required', 'array'],
        ]);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $device = $request->attributes->get('pos_device');
            $results = $this->push_service->push($device, $request->input('transactions', []));

            return $this->success('Push complete.', ['results' => $results]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function health()
    {
        return $this->success('OK', [
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
