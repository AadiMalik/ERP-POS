<?php

namespace App\Services\Concrete\Api\Offline;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Branch;
use App\Models\Business;
use App\Models\PosRegister;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Concrete\Admin\FeatureLimitService;

class OfflineSetupService
{
    protected $feature_limit_service;

    public function __construct(FeatureLimitService $feature_limit_service)
    {
        $this->feature_limit_service = $feature_limit_service;
    }

    /**
     * Lightweight pre-login check: business exists, is active, and POS is enabled on its package.
     */
    public function validateBusiness(string $business_id): array
    {
        $business = Business::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->first();

        if (!$business) {
            throw new \Exception('Business not found or inactive.');
        }

        $business->loadMissing('package.modules');

        if (!$business->package || !$business->package->moduleEnabled('pos')) {
            throw new \Exception('POS module is not enabled for this business subscription.');
        }

        return [
            'business_id' => $business->business_id,
            'name' => $business->name,
            'code' => $business->code,
        ];
    }

    /**
     * Branches, warehouses, and registers for the locked business — used during desktop setup after login.
     */
    public function getLocationOptions(User $user, string $business_id): array
    {
        $this->assertUserCanAccessBusiness($user, $business_id);

        return $this->exportLocationOptions($business_id);
    }

    /**
     * Pre-login setup payload: POS staff users (with password hashes for offline login),
     * branches, warehouses, and registers for the validated business.
     */
    public function fetchBusinessSetupData(string $business_id): array
    {
        $business = $this->validateBusiness($business_id);
        $sync_service = app(OfflineSyncService::class);
        $auth_service = app(OfflineAuthService::class);

        $users = collect($sync_service->exportPosUsers($business_id))
            ->map(function (array $row) use ($auth_service, $business_id) {
                $user = User::where('id', $row['id'])->where('is_deleted', 0)->first();
                $row['business_id'] = $business_id;
                $row['permissions'] = $user ? $auth_service->collectPermissions($user) : [];

                return $row;
            })
            ->values()
            ->all();

        $locations = $this->exportLocationOptions($business_id);

        return array_merge([
            'business' => $business,
            'users' => $users,
            'user_count' => count($users),
        ], $locations);
    }

    /**
     * Register a desktop device during setup using staff credentials (no prior bearer token).
     */
    public function registerDeviceWithCredentials(array $payload): array
    {
        $login = app(OfflineAuthService::class)->login(
            $payload['email'],
            $payload['password'],
            $payload['business_id']
        );

        $user = User::where('id', $login['user']['id'])->where('is_deleted', 0)->first();
        if (!$user) {
            throw new \Exception('Authenticated user could not be loaded.');
        }

        $device = app(OfflineDeviceService::class)->register($user, $payload);

        return array_merge($device, [
            'auth_token' => $login['token'],
            'user' => $login['user'],
            'permissions' => $login['permissions'],
            'password_hash' => $login['password_hash'],
        ]);
    }

    protected function exportLocationOptions(string $business_id): array
    {
        $branches = Branch::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->orderBy('name')
            ->get(['branch_id', 'business_id', 'code', 'name', 'status']);

        $warehouses = Warehouse::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->orderBy('name')
            ->get(['warehouse_id', 'business_id', 'branch_id', 'code', 'name', 'status']);

        $registers = PosRegister::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->orderBy('name')
            ->get(['pos_register_id', 'business_id', 'branch_id', 'warehouse_id', 'name', 'code', 'mode', 'status']);

        return [
            'business_id' => $business_id,
            'branches' => $branches,
            'warehouses' => $warehouses,
            'registers' => $registers,
        ];
    }

    public function assertUserCanAccessBusiness(User $user, string $business_id): void
    {
        $role = $user->roles()->value('name');

        if ($role === RoleNames::SUPERADMIN) {
            return;
        }

        if (empty($user->business_id)) {
            throw new \Exception('Your account is not linked to a business.');
        }

        if ($user->business_id !== $business_id) {
            throw new \Exception('You cannot access POS data for another business.');
        }
    }

    public function assertLocationBelongsToBusiness(string $business_id, ?string $branch_id, ?string $warehouse_id, ?string $pos_register_id): void
    {
        if ($branch_id) {
            $branch = Branch::where('branch_id', $branch_id)
                ->where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->first();

            if (!$branch) {
                throw new \Exception('Invalid branch for this business.');
            }
        }

        if ($warehouse_id) {
            $warehouse = Warehouse::where('warehouse_id', $warehouse_id)
                ->where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->first();

            if (!$warehouse) {
                throw new \Exception('Invalid warehouse for this business.');
            }

            if ($branch_id && $warehouse->branch_id && $warehouse->branch_id !== $branch_id) {
                throw new \Exception('Warehouse does not belong to the selected branch.');
            }
        }

        if ($pos_register_id) {
            $register = PosRegister::where('pos_register_id', $pos_register_id)
                ->where('business_id', $business_id)
                ->where('is_deleted', 0)
                ->first();

            if (!$register) {
                throw new \Exception('Invalid register for this business.');
            }

            if ($branch_id && $register->branch_id !== $branch_id) {
                throw new \Exception('Register does not belong to the selected branch.');
            }

            if ($warehouse_id && $register->warehouse_id && $register->warehouse_id !== $warehouse_id) {
                throw new \Exception('Register warehouse does not match the selected warehouse.');
            }
        }
    }
}
