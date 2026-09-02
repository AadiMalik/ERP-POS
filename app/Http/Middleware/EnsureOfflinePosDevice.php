<?php

namespace App\Http\Middleware;

use App\Models\PosDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Validates the X-Pos-Device-Id + X-Pos-Device-Token headers for offline
 * desktop POS API requests. Device tokens are issued at registration time
 * and stored hashed server-side (same pattern as Sanctum personal access tokens).
 */
class EnsureOfflinePosDevice
{
    public function handle(Request $request, Closure $next)
    {
        $device_id = $request->header('X-Pos-Device-Id');
        $device_token = $request->header('X-Pos-Device-Token');

        if (empty($device_id) || empty($device_token)) {
            return response()->json([
                'Success' => false,
                'Message' => 'POS device credentials are required.',
                'Status' => 401,
            ], 401);
        }

        $device = PosDevice::where('pos_device_id', $device_id)
            ->where('is_deleted', 0)
            ->where('status', 'active')
            ->first();

        if (!$device || !Hash::check($device_token, $device->api_token_hash)) {
            return response()->json([
                'Success' => false,
                'Message' => 'Invalid POS device credentials.',
                'Status' => 401,
            ], 401);
        }

        $device->last_seen_at = now();
        $device->save();

        $request->attributes->set('pos_device', $device);

        return $next($request);
    }
}
