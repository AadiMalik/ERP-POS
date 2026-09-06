<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;

/**
 * Super Admin screen to enable/disable a whole business's access to ERP,
 * Website & Mobile App (Storefront), POS, and Offline POS - see
 * App\Http\Middleware\EnsurePlatformAccess, the enforcement side of these
 * same 4 columns on `businesses`.
 */
class BusinessAccessControlController extends Controller
{
    use ResponseAPI;

    protected BusinessService $business_service;

    public function __construct(BusinessService $business_service)
    {
        $this->middleware('permission:business-access-control.view')->only(['index', 'getData']);
        $this->middleware('permission:business-access-control.manage')->only(['toggle']);

        $this->business_service = $business_service;
    }

    public function index()
    {
        return view('admin.business-access-control.index');
    }

    public function getData(Request $request)
    {
        return $this->business_service->getAccessControlData($request->all());
    }

    public function toggle($business_id, $platform)
    {
        try {
            $this->business_service->togglePlatformAccess($business_id, $platform);

            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
