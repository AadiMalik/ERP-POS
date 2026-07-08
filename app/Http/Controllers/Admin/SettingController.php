<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CommonService;
use App\Services\Concrete\Admin\SettingService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    use ResponseAPI;

    protected $business_service;
    protected $setting_service;
    protected $account_service;
    protected $common_service;

    public function __construct(
        BusinessService $business_service,
        SettingService $setting_service,
        AccountService $account_service,
        CommonService $common_service
    ) {
        $this->business_service = $business_service;
        $this->setting_service = $setting_service;
        $this->account_service = $account_service;
        $this->common_service = $common_service;
    }

    public function index()
    {
        $business =  $this->business_service->getAll();
        $accounts =  $this->account_service->getAllChild();
        $business_setting = $this->setting_service->getBusinessSetting(Auth::user()->business_id);
        $accounting_setting = $this->setting_service->getAccountingSetting(Auth::user()->business_id);
        $customer_setting = $this->setting_service->getCustomerSetting(Auth::user()->business_id);
        $supplier_setting = $this->setting_service->getSupplierSetting(Auth::user()->business_id);
        $inventory_setting = $this->setting_service->getInventorySetting(Auth::user()->business_id);
        $email_setting = $this->setting_service->getEmailSetting(Auth::user()->business_id);
        $sms_setting = $this->setting_service->getSmsSetting(Auth::user()->business_id);
        $fbr_setting = $this->setting_service->getFbrSetting(Auth::user()->business_id);
        $whatsapp_setting = $this->setting_service->getWhatsappSetting(Auth::user()->business_id);
        $timezones = $this->common_service->getAllTimezone();
        return view('admin.setting.index', compact(
            'business',
            'accounts',
            'business_setting',
            'accounting_setting',
            'customer_setting',
            'supplier_setting',
            'inventory_setting',
            'email_setting',
            'sms_setting',
            'fbr_setting',
            'whatsapp_setting',
            'timezones'
        ));
    }

    public function updateBusinessSetting(Request $request)
    {
        $rules = [
            'timezone'    => 'required',
            'tax_type'    => 'required|in:inclusive,exclusive',
            'tax_rate'    => 'nullable|numeric|min:0',
            'date_format' => 'required',
            'time_format' => 'required',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $setting = $this->setting_service->updateBusinessSetting($obj);
        if ($setting) {
            return $this->success(
                Message::UPDATE,
                $setting
            );
        } else {
            return $this->error(Message::NOTUPDATE);
        }
    }
}
