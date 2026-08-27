<?php

namespace App\Services\Concrete\Admin;

use App\Models\FirebaseSetting;
use App\Models\UserFcmToken;
use App\Repository\Repository;
use App\Services\Concrete\Firebase\FirebaseNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FirebaseSettingService
{
    protected $model;

    public function __construct()
    {
        $this->model = new Repository(new FirebaseSetting());
    }

    public function getByBusiness($businessId): FirebaseSetting
    {
        $setting = $this->model->getModel()::firstOrCreate(
            ['business_id' => $businessId],
            [
                'firebase_setting_id' => generateUuid(),
                'is_active' => false,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]
        );

        return $setting;
    }

    public function save(array $obj): FirebaseSetting
    {
        $businessId = $obj['business_id'];
        $setting = $this->model->getModel()::firstOrNew(['business_id' => $businessId]);

        if (!$setting->exists) {
            $setting->firebase_setting_id = generateUuid();
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->project_id = $obj['project_id'] ?? null;
        $setting->client_email = $obj['client_email'] ?? null;

        // Keep existing private key when the form leaves it blank (edit UX).
        if (!empty($obj['private_key'])) {
            $setting->private_key = $obj['private_key'];
        }

        $setting->is_active = (bool) ($obj['is_active'] ?? false);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        Cache::forget('firebase_oauth_' . $businessId);

        return $setting;
    }

    public function hasValidConfiguration($businessId): bool
    {
        return app(FirebaseNotificationService::class)->hasValidConfiguration($businessId);
    }
}
