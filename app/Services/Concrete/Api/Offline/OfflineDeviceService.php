<?php

namespace App\Services\Concrete\Api\Offline;

use App\Models\PosDevice;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OfflineDeviceService
{
  public function register(User $user, array $payload): array
  {
    if (!$user->can('pos.access')) {
      throw new \Exception('You do not have permission to register POS devices.');
    }

    $business_id = $payload['business_id'] ?? $user->business_id;
    $branch_id = $payload['branch_id'] ?? $user->branch_id;

    if (empty($business_id)) {
      throw new \Exception('Business is required to register a device.');
    }

    if ($user->business_id && $user->business_id !== $business_id) {
      throw new \Exception('You cannot register a device for another business.');
    }

    $branch_id = $payload['branch_id'] ?? $user->branch_id;
    $warehouse_id = $payload['warehouse_id'] ?? null;

    app(OfflineSetupService::class)->assertLocationBelongsToBusiness(
      $business_id,
      $branch_id,
      $warehouse_id,
      $payload['pos_register_id'] ?? null
    );

    if (empty($branch_id) || empty($warehouse_id)) {
      throw new \Exception('Branch and warehouse are required to register a desktop POS device.');
    }

    $plain_token = Str::random(64);
    $device_id = (string) Str::uuid();

    $device = PosDevice::create([
      'pos_device_id' => $device_id,
      'business_id' => $business_id,
      'branch_id' => $branch_id,
      'warehouse_id' => $warehouse_id,
      'pos_register_id' => $payload['pos_register_id'] ?? null,
      'name' => $payload['name'] ?? ('POS Device ' . substr($device_id, 0, 8)),
      'device_fingerprint' => $payload['device_fingerprint'] ?? null,
      'api_token_hash' => Hash::make($plain_token),
      'status' => 'active',
      'sync_cursors' => [],
      'createdby_id' => $user->id,
      'date_created' => now(),
    ]);

    return [
      'device' => $this->formatDevice($device),
      'device_token' => $plain_token,
    ];
  }

  public function formatDevice(PosDevice $device): array
  {
    return [
      'pos_device_id' => $device->pos_device_id,
      'business_id' => $device->business_id,
      'branch_id' => $device->branch_id,
      'warehouse_id' => $device->warehouse_id,
      'pos_register_id' => $device->pos_register_id,
      'name' => $device->name,
      'last_sync_at' => optional($device->last_sync_at)->toIso8601String(),
      'sync_cursors' => $device->sync_cursors ?? [],
    ];
  }

  public function updateCursors(PosDevice $device, array $cursors): void
  {
    $device->sync_cursors = array_merge($device->sync_cursors ?? [], $cursors);
    $device->last_sync_at = now();
    $device->date_updated = now();
    $device->save();
  }
}
