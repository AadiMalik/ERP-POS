<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionSettingController extends Controller
{
    public function edit()
    {
        $setting = SubscriptionSetting::current();
        return view('admin.subscription-settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = SubscriptionSetting::where('is_deleted', 0)->first();

        $thresholds = collect(explode(',', $request->reminder_thresholds_days))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        $data = [
            'default_grace_period_days' => $request->default_grace_period_days ?? 7,
            'reminder_thresholds_days' => $thresholds ?: [30, 15, 7, 3, 1],
            'restrict_access_in_grace_period' => $request->boolean('restrict_access_in_grace_period'),
            'invoice_prefix' => $request->invoice_prefix ?: 'SUB-INV',
        ];

        if ($setting) {
            $data['updatedby_id'] = Auth::id();
            $data['date_updated'] = now();
            $setting->update($data);
        } else {
            $data['subscription_setting_id'] = generateUuid();
            $data['is_deleted'] = 0;
            $data['createdby_id'] = Auth::id();
            $data['date_created'] = now();
            SubscriptionSetting::create($data);
        }

        return redirect()->route('subscription-settings.edit')->with('success', 'Subscription settings updated successfully.');
    }
}
