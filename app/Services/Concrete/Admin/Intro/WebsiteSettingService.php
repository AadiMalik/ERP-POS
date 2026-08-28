<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroWebsiteSetting;
use Illuminate\Support\Facades\Auth;

class WebsiteSettingService
{
    public function allGrouped()
    {
        return IntroWebsiteSetting::orderBy('group')->orderBy('key')->get()->groupBy('group');
    }

    public function asMap(): array
    {
        return IntroWebsiteSetting::pluck('value', 'key')->toArray();
    }

    public function get(string $key, $default = null)
    {
        $row = IntroWebsiteSetting::where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    public function upsertMany(array $pairs, string $group = 'general')
    {
        foreach ($pairs as $key => $value) {
            $type = 'text';
            if (is_bool($value) || $value === '0' || $value === '1' || $value === 'true' || $value === 'false') {
                $type = 'boolean';
            } elseif (is_array($value)) {
                $type = 'json';
                $value = json_encode($value);
            }

            IntroWebsiteSetting::updateOrCreate(
                ['key' => $key],
                [
                    'intro_website_setting_id' => IntroWebsiteSetting::where('key', $key)->value('intro_website_setting_id') ?: generateUuid(),
                    'group' => $group,
                    'value' => is_bool($value) ? ($value ? '1' : '0') : $value,
                    'type' => $type,
                    'label' => ucwords(str_replace('_', ' ', $key)),
                    'updatedby_id' => Auth::id(),
                    'date_updated' => now(),
                ]
            );
        }
        return $this->asMap();
    }

    public function publicMap(): array
    {
        $hidden = ['smtp_password', 'api_secret'];
        $map = IntroWebsiteSetting::whereNotIn('key', $hidden)->pluck('value', 'key')->toArray();

        foreach (['logo', 'logo_light', 'favicon', 'og_image'] as $key) {
            $map[$key . '_url'] = !empty($map[$key])
                ? asset('public/uploads/intro/settings/' . $map[$key])
                : null;
        }

        return $map;
    }
}

