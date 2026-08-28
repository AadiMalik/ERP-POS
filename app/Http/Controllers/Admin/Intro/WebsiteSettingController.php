<?php

namespace App\Http\Controllers\Admin\Intro;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\Intro\WebsiteSettingService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class WebsiteSettingController extends Controller
{
    use ResponseAPI;

    protected $service;

    public function __construct(WebsiteSettingService $service)
    {
        $this->middleware('superadmin');
        $this->middleware('permission:intro-website-setting.view')->only(['index', 'show']);
        $this->middleware('permission:intro-website-setting.edit')->only(['update']);
        $this->service = $service;
    }

    public function index()
    {
        $map = $this->service->asMap();
        return view('admin.intro.website_settings.index', compact('map'));
    }

    public function show()
    {
        return $this->success(Message::FETCH, [
            'grouped' => $this->service->allGrouped(),
            'map' => $this->service->asMap(),
        ]);
    }

    public function update(Request $request)
    {
        try {
            $settings = $request->input('settings', $request->except([
                '_token', 'logo', 'logo_light', 'favicon', 'og_image', 'group',
            ]));
            if ($request->hasFile('logo')) {
                $settings['logo'] = $this->storeSettingImage($request->file('logo'), 'logo');
            }
            if ($request->hasFile('logo_light')) {
                $settings['logo_light'] = $this->storeSettingImage($request->file('logo_light'), 'logo_light');
            }
            if ($request->hasFile('favicon')) {
                $settings['favicon'] = $this->storeSettingImage($request->file('favicon'), 'favicon');
            }
            if ($request->hasFile('og_image')) {
                $settings['og_image'] = $this->storeSettingImage($request->file('og_image'), 'og_image');
            }
            $group = $request->input('group', 'general');
            // Keep social_* keys in the social group so admin filters stay consistent
            $social = [];
            $rest = [];
            foreach ($settings as $key => $value) {
                if (str_starts_with((string) $key, 'social_')) {
                    $social[$key] = $value;
                } else {
                    $rest[$key] = $value;
                }
            }
            if ($rest) {
                $this->service->upsertMany($rest, $group);
            }
            if ($social) {
                $this->service->upsertMany($social, 'social');
            }
            $map = $this->service->asMap();
            return $this->success(Message::UPDATE, $map);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function storeSettingImage($file, string $key): string
    {
        $path = public_path('uploads/intro/settings');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $name = $key . '_' . time() . '_' . $file->getClientOriginalName();
        $file->move($path, $name);
        return $name;
    }
}
