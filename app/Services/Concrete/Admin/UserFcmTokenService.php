<?php

namespace App\Services\Concrete\Admin;

use App\Models\UserFcmToken;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Device-token registry for future mobile APIs. Admin broadcast selection
 * reads active tokens; registration endpoints can call registerOrUpdate().
 */
class UserFcmTokenService
{
    protected $model;

    public function __construct()
    {
        $this->model = new Repository(new UserFcmToken());
    }

    /**
     * Upsert a token for a user within a business. Reassigns the token row
     * if it already exists for another user in the same business.
     */
    public function registerOrUpdate(array $obj): UserFcmToken
    {
        return DB::transaction(function () use ($obj) {
            $existing = UserFcmToken::where('business_id', $obj['business_id'])
                ->where('fcm_token', $obj['fcm_token'])
                ->first();

            if ($existing) {
                $existing->fill([
                    'user_id' => $obj['user_id'],
                    'device_id' => $obj['device_id'] ?? $existing->device_id,
                    'device_type' => $obj['device_type'] ?? $existing->device_type,
                    'is_active' => true,
                    'last_seen_at' => now(),
                    'last_used_at' => now(),
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]);
                $existing->save();

                return $existing;
            }

            return UserFcmToken::create([
                'user_fcm_token_id' => generateUuid(),
                'business_id' => $obj['business_id'],
                'user_id' => $obj['user_id'],
                'fcm_token' => $obj['fcm_token'],
                'device_id' => $obj['device_id'] ?? null,
                'device_type' => $obj['device_type'] ?? null,
                'is_active' => true,
                'last_seen_at' => now(),
                'last_used_at' => now(),
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);
        });
    }

    public function getActiveTokensForUsers(string $businessId, array $userIds)
    {
        return UserFcmToken::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->whereIn('user_id', $userIds)
            ->get();
    }

    /**
     * Users (customers/staff) in this business that currently have ≥1 active token.
     */
    public function getUsersWithActiveTokens(string $businessId)
    {
        return UserFcmToken::query()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->with('user:id,name,email,phone')
            ->get()
            ->groupBy('user_id')
            ->map(function ($tokens) {
                $user = $tokens->first()->user;
                if (!$user) {
                    return null;
                }

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'token_count' => $tokens->count(),
                    'devices' => $tokens->map(fn ($t) => [
                        'user_fcm_token_id' => $t->user_fcm_token_id,
                        'device_type' => $t->device_type,
                        'device_id' => $t->device_id,
                    ])->values(),
                ];
            })
            ->filter()
            ->values();
    }
}
