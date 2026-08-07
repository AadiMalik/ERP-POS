<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmailProvider;
use App\Enums\Message;
use App\Enums\SMSProvider;
use App\Enums\WhatsappProvider;
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
        $email_mailer = EmailProvider::getoptions();
        $sms_provider = SMSProvider::getOptions();
        $whatsapp_provider = WhatsappProvider::getOptions();
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
            'timezones',
            'email_mailer',
            'sms_provider',
            'whatsapp_provider'
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

    public function updateAccountingSetting(Request $request)
    {
        $rules = [
            'default_cash_account_id'            => 'nullable|exists:accounts,account_id',
            'default_bank_account_id'            => 'nullable|exists:accounts,account_id',
            'default_discount_account_id'        => 'nullable|exists:accounts,account_id',
            'default_tax_account_id'             => 'nullable|exists:accounts,account_id',
            'default_revenue_account_id'         => 'nullable|exists:accounts,account_id',
            'default_purchase_account_id'        => 'nullable|exists:accounts,account_id',
            'default_expense_account_id'         => 'nullable|exists:accounts,account_id',
            'default_supplier_account_id'        => 'nullable|exists:accounts,account_id',
            'default_customer_account_id'        => 'nullable|exists:accounts,account_id',
            'default_carriage_account_id'        => 'nullable|exists:accounts,account_id',
            'default_round_off_account_id'       => 'nullable|exists:accounts,account_id',
            'default_purchase_return_account_id' => 'nullable|exists:accounts,account_id',
            'default_sale_account_id'            => 'nullable|exists:accounts,account_id',
            'default_sale_return_account_id'     => 'nullable|exists:accounts,account_id',
            'default_inventory_account_id'       => 'nullable|exists:accounts,account_id',
            'default_withholding_tax_account_id' => 'nullable|exists:accounts,account_id',
            'manual_payment_account_selection'   => 'nullable|boolean',

            'currency'          => 'required|string|max:20',
            'currency_symbol'   => 'required|string|max:10',
            'currency_position' => 'required|in:before,after',
            'decimal_points'    => 'required|integer|min:0|max:6',
            'aging_basis'       => 'nullable|in:due_date,invoice_date',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateAccountingSetting($obj);

        if ($setting) {
            return $this->success(
                Message::UPDATE,
                $setting
            );
        }

        return $this->error(Message::NOTUPDATE);
    }

    public function updateCustomerSetting(Request $request)
    {
        $rules = [
            'customer_code_prefix'      => 'nullable|string|max:20',
            'customer_enable_credit_limit'       => 'required|boolean',
            'customer_credit_limit'              => 'required_if:customer_enable_credit_limit,1|nullable|numeric|min:0',

            'loyalty_program'           => 'required|boolean',
            'loyalty_every_amount'      => 'required_if:loyalty_program,1|nullable|numeric|min:0',
            'loyalty_point_rate'        => 'required_if:loyalty_program,1|nullable|numeric|min:0',
            'loyalty_min_order_amount'  => 'required_if:loyalty_program,1|nullable|numeric|min:0',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['enable_credit_limit'] = $request->customer_enable_credit_limit ? 1 : 0;
        $obj['credit_limit'] = $request->customer_credit_limit ?? 0;
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateCustomerSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateSupplierSetting(Request $request)
    {
        $rules = [
            'supplier_code_prefix' => 'nullable|string|max:20',
            'supplier_enable_credit_limit'  => 'required|boolean',
            'supplier_credit_limit'         => 'required_if:supplier_enable_credit_limit,1|nullable|numeric|min:0',
            'default_payment_days' => 'nullable|integer|min:0',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['enable_credit_limit'] = $request->supplier_enable_credit_limit ? 1 : 0;
        $obj['credit_limit'] = $request->supplier_credit_limit ?? 0;
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateSupplierSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateEmailSetting(Request $request)
    {
        $rules = [
            'enable_email_notifications' => 'required|boolean',
            
            'mail_mailer'        => 'required_if:enable_email_notifications,1|in:'.EmailProvider::SMTP.','.EmailProvider::SENDMAIL.','.EmailProvider::MAILGUN.',',
            'mail_host'          => 'required_if:enable_email_notifications,1',
            'mail_port'          => 'required_if:enable_email_notifications,1|numeric',
            'mail_username'      => 'required_if:enable_email_notifications,1',
            'mail_password'      => 'required_if:enable_email_notifications,1',
            'mail_encryption'    => 'nullable|in:tls,ssl',
            'mail_from_address'  => 'required_if:enable_email_notifications,1|email',
            'mail_from_name'     => 'required_if:enable_email_notifications,1',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateEmailSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateSmsSetting(Request $request)
    {
        $rules = [

            'enable_sms' => 'required|boolean',

            'provider' => 'required_if:enable_sms,1|in:'.SMSProvider::TWILIO.','.SMSProvider::INFOBIP.','.SMSProvider::BRANDSMS.','.SMSProvider::MSG91.','.SMSProvider::VONAGE.',',

            // Common
            'base_url' => 'nullable|string|max:255',
            'api_key' => 'nullable|string',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string',
            'account_sid' => 'nullable|string|max:255',
            'auth_token' => 'nullable|string',
            'sender_id' => 'nullable|string|max:255',
            'template_id' => 'nullable|string|max:255',
            'entity_id' => 'nullable|string|max:255',
            'flow_id' => 'nullable|string|max:255',

            // Notification Settings
            'send_invoice_sms' => 'required_if:enable_sms,1|boolean',
            'send_due_sms' => 'required_if:enable_sms,1|boolean',

        ];

        $validator = Validator::make($request->all(), $rules);

        $validator->sometimes(
            ['account_sid', 'auth_token', 'sender_id'],
            'required',
            function ($input) {
                return $input->enable_sms && $input->provider == SMSProvider::TWILIO;
            }
        );

        $validator->sometimes(
            ['base_url', 'api_key', 'sender_id'],
            'required',
            function ($input) {
                return $input->enable_sms && $input->provider == SMSProvider::INFOBIP;
            }
        );

        $validator->sometimes(
            ['base_url', 'username', 'password', 'sender_id'],
            'required',
            function ($input) {
                return $input->enable_sms && $input->provider == SMSProvider::BRANDSMS;
            }
        );

        $validator->sometimes(
            ['api_key', 'sender_id', 'template_id', 'flow_id'],
            'required',
            function ($input) {
                return $input->enable_sms && $input->provider == SMSProvider::MSG91;
            }
        );

        $validator->sometimes(
            ['api_key', 'password', 'sender_id'],
            'required',
            function ($input) {
                return $input->enable_sms && $input->provider == SMSProvider::VONAGE;
            }
        );

        if ($validator->fails()) {
            return $this->validationResponse(
                $validator->errors()->first()
            );
        }

        $obj = $request->all();
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateSmsSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateWhatsappSetting(Request $request)
    {
        $rules = [
            'enable_whatsapp' => 'required|boolean',

            'provider'        => 'required_if:enable_whatsapp,1|in:'.WhatsappProvider::META.','.WhatsappProvider::TWILIO.','.WhatsappProvider::ULTRAMSG.','.WhatsappProvider::GREENAPI,
            'api_key'         => 'nullable',
            'access_token'    => 'nullable',
            'instance_id'     => 'nullable',
            'phone_number_id' => 'nullable',
            'webhook_url'     => 'nullable|url',
            'send_invoice'    => 'required_if:enable_whatsapp,1|boolean',
            'send_receipt'    => 'required_if:enable_whatsapp,1|boolean',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateWhatsappSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateFbrSetting(Request $request)
    {
        $rules = [
            'enable_fbr' => 'required|boolean',

            'fbr_environment'    => 'required_if:enable_fbr,1|in:sandbox,production',
            'fbr_pos_id'         => 'required_if:enable_fbr,1',
            'fbr_license_key'    => 'required_if:enable_fbr,1',
            'fbr_ntn'            => 'required_if:enable_fbr,1',
            'fbr_strn'           => 'required_if:enable_fbr,1',
            'fbr_sandbox_url'    => 'required_if:enable_fbr,1|url',
            'fbr_production_url' => 'required_if:enable_fbr,1|url',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateFbrSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }
}
