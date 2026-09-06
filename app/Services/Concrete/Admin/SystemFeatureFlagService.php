<?php

namespace App\Services\Concrete\Admin;

use App\Models\SystemFeatureFlag;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Super Admin, platform-wide on/off registry for system features/services/
 * integrations - independent of per-business Package module-tier gating
 * (FeatureLimitService) and of per-business platform access
 * (EnsurePlatformAccess). An unregistered key fails open (not restricted) so
 * this can never accidentally break behavior that hasn't opted in.
 */
class SystemFeatureFlagService
{
    public function isEnabled(string $key): bool
    {
        $flag = SystemFeatureFlag::where('key', $key)->first();

        return $flag ? (bool) $flag->is_enabled : true;
    }

    public function getData($data)
    {
        return DataTables::of(SystemFeatureFlag::query())
            ->addColumn('is_enabled', function ($item) {
                $checked = $item->is_enabled ? 'checked' : '';

                return '
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input toggleSystemFeatureFlag" type="checkbox" data-id="' . $item->system_feature_flag_id . '" ' . $checked . '>
                    </div>
                ';
            })
            ->rawColumns(['is_enabled'])
            ->make(true);
    }

    public function toggle(string $id): void
    {
        $flag = SystemFeatureFlag::findOrFail($id);

        $flag->update([
            'is_enabled' => !$flag->is_enabled,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ]);
    }
}
