<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\SystemFeatureFlagService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

class SystemFeatureFlagController extends Controller
{
    use ResponseAPI;

    protected SystemFeatureFlagService $system_feature_flag_service;

    public function __construct(SystemFeatureFlagService $system_feature_flag_service)
    {
        $this->middleware('permission:system-feature-flag.view')->only(['index', 'getData']);
        $this->middleware('permission:system-feature-flag.manage')->only(['toggle']);

        $this->system_feature_flag_service = $system_feature_flag_service;
    }

    public function index()
    {
        return view('admin.system-feature-flag.index');
    }

    public function getData(Request $request)
    {
        return $this->system_feature_flag_service->getData($request->all());
    }

    public function toggle($id)
    {
        try {
            $this->system_feature_flag_service->toggle($id);

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
