<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BarcodeType;
use App\Enums\EmailProvider;
use App\Enums\Message;
use App\Enums\QrDataSource;
use App\Enums\SMSProvider;
use App\Enums\WhatsappProvider;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\CommonService;
use App\Services\Concrete\Admin\CustomerService;
use App\Models\Order;
use App\Services\Concrete\Admin\PrintSettingResolverService;
use App\Services\Concrete\Admin\SaleTypeService;
use App\Services\Concrete\Admin\SettingService;
use App\Services\Concrete\Admin\ThermalPrintSettingResolverService;
use App\Support\Print\ThermalPrintConfig;
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
    protected $print_setting_resolver;
    protected $customer_service;
    protected $thermal_print_setting_resolver;
    protected $sale_type_service;
    protected $branch_service;

    public function __construct(
        BusinessService $business_service,
        SettingService $setting_service,
        AccountService $account_service,
        CommonService $common_service,
        PrintSettingResolverService $print_setting_resolver,
        CustomerService $customer_service,
        ThermalPrintSettingResolverService $thermal_print_setting_resolver,
        SaleTypeService $sale_type_service,
        BranchService $branch_service
    ) {
        $this->middleware('permission:setting.manage');

        $this->business_service = $business_service;
        $this->setting_service = $setting_service;
        $this->account_service = $account_service;
        $this->common_service = $common_service;
        $this->print_setting_resolver = $print_setting_resolver;
        $this->customer_service = $customer_service;
        $this->thermal_print_setting_resolver = $thermal_print_setting_resolver;
        $this->sale_type_service = $sale_type_service;
        $this->branch_service = $branch_service;
    }

    public function index(Request $request)
    {
        $business =  $this->business_service->getAll();
        $thermal_branches = $this->branch_service->getAllActive();
        $thermal_branch_id = $request->query('thermal_branch_id') ?: null;
        // Explicitly nullable (not empty()-defaulted) so "no row saved for
        // this branch yet" (null) is distinguishable in the view from "this
        // is the business default" (business default is always resolvable).
        $thermal_print_setting = $thermal_branch_id
            ? $this->setting_service->getBranchThermalPrintSetting(Auth::user()->business_id, $thermal_branch_id)
            : $this->setting_service->getThermalPrintSetting(Auth::user()->business_id);
        $accounts =  $this->account_service->getAllChild();
        $business_setting = $this->setting_service->getBusinessSetting(Auth::user()->business_id);
        $accounting_setting = $this->setting_service->getAccountingSetting(Auth::user()->business_id);
        $customer_setting = $this->setting_service->getCustomerSetting(Auth::user()->business_id);
        $supplier_setting = $this->setting_service->getSupplierSetting(Auth::user()->business_id);
        $inventory_setting = $this->setting_service->getInventorySetting(Auth::user()->business_id);
        $notification_setting = $this->setting_service->getNotificationSetting(Auth::user()->business_id);
        $email_setting = $this->setting_service->getEmailSetting(Auth::user()->business_id);
        $sms_setting = $this->setting_service->getSmsSetting(Auth::user()->business_id);
        $fbr_setting = $this->setting_service->getFbrSetting(Auth::user()->business_id);
        $whatsapp_setting = $this->setting_service->getWhatsappSetting(Auth::user()->business_id);
        $print_setting = $this->setting_service->getPrintSetting(Auth::user()->business_id);
        $barcode_setting = $this->setting_service->getBarcodeSetting(Auth::user()->business_id);
        $theme_setting = $this->setting_service->getThemeSetting(Auth::user()->business_id);
        $pos_setting = $this->setting_service->getPosSetting(Auth::user()->business_id);
        $pra_setting = $this->setting_service->getPraSetting(Auth::user()->business_id);
        $pos_customers = $this->customer_service->getAllActive(Auth::user()->business_id);
        $sale_types = $this->sale_type_service->getAll(Auth::user()->business_id);
        $theme_presets = config('theme_presets');
        $timezones = $this->common_service->getAllTimezone();
        $email_mailer = EmailProvider::getoptions();
        $sms_provider = SMSProvider::getOptions();
        $whatsapp_provider = WhatsappProvider::getOptions();
        $barcode_types = BarcodeType::getOptions();
        $qr_data_sources = QrDataSource::getOptions();
        return view('admin.setting.index', compact(
            'business',
            'accounts',
            'business_setting',
            'accounting_setting',
            'customer_setting',
            'supplier_setting',
            'inventory_setting',
            'notification_setting',
            'email_setting',
            'sms_setting',
            'fbr_setting',
            'whatsapp_setting',
            'print_setting',
            'barcode_setting',
            'theme_setting',
            'pos_setting',
            'pra_setting',
            'thermal_print_setting',
            'thermal_branches',
            'thermal_branch_id',
            'pos_customers',
            'sale_types',
            'theme_presets',
            'timezones',
            'email_mailer',
            'sms_provider',
            'whatsapp_provider',
            'barcode_types',
            'qr_data_sources'
        ));
    }

    public function updateBusinessSetting(Request $request)
    {
        $rules = [
            'timezone'         => 'required',
            'overall_tax_rate' => 'nullable|numeric|min:0|max:100',
            'card_tax_rate'    => 'nullable|numeric|min:0|max:100',
            'date_format'      => 'required',
            'time_format'      => 'required',
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
            'default_store_credit_account_id'    => 'nullable|exists:accounts,account_id',
            'default_carriage_account_id'        => 'nullable|exists:accounts,account_id',
            'default_round_off_account_id'       => 'nullable|exists:accounts,account_id',
            'default_purchase_return_account_id' => 'nullable|exists:accounts,account_id',
            'default_service_purchase_account_id' => 'nullable|exists:accounts,account_id',
            'default_service_purchase_return_account_id' => 'nullable|exists:accounts,account_id',
            'default_service_sale_account_id'    => 'nullable|exists:accounts,account_id',
            'default_service_sale_return_account_id' => 'nullable|exists:accounts,account_id',
            'default_sale_account_id'            => 'nullable|exists:accounts,account_id',
            'default_sale_return_account_id'     => 'nullable|exists:accounts,account_id',
            'default_inventory_account_id'       => 'nullable|exists:accounts,account_id',
            'default_cogs_account_id'            => 'nullable|exists:accounts,account_id',
            'default_opening_stock_account_id'   => 'nullable|exists:accounts,account_id',
            'default_stock_adjustment_account_id' => 'nullable|exists:accounts,account_id',
            'default_withholding_tax_account_id' => 'nullable|exists:accounts,account_id',
            'manual_payment_account_selection'   => 'nullable|boolean',

            'currency'          => 'required|string|max:20',
            'currency_symbol'   => 'required|string|max:10',
            'currency_position' => 'required|in:before,after',
            'decimal_points'    => 'required|integer|min:0|max:6',
            'aging_basis'       => 'nullable|in:due_date,invoice_date',

            'period_closing_mode'      => 'nullable|in:monthly,yearly,manual',
            'budgeting_mode'           => 'nullable|in:auto,manual',
            'budget_growth_percent'    => 'nullable|numeric|min:-100|max:1000',
            'advanced_accounting_mode' => 'nullable|boolean',
            'fiscal_year_start_month'  => 'nullable|integer|min:1|max:12',
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

    public function updatePosSetting(Request $request)
    {
        $rules = [
            'register_mode'            => 'nullable|in:manual,automatic',
            'open_time'                => 'nullable|date_format:H:i',
            'close_time'               => 'nullable|date_format:H:i',
            'invoice_prefix'           => 'nullable|string|max:50',
            'invoice_start'            => 'nullable|integer|min:1',
            'invoice_footer'           => 'nullable|string',
            'default_customer_user_id' => 'nullable|exists:users,id',
            'default_payment_method_id' => 'nullable',
            'default_order_type_id'    => 'nullable',
            'default_order_source_id'  => 'nullable',
            'enable_discount'          => 'nullable|boolean',
            'discount_level'           => 'nullable|in:line,order,both',
            'allow_backdated_sale'     => 'nullable|boolean',
            'backdated_sale_max_days'  => 'nullable|integer|min:0',
            'daily_order_id_reset'     => 'nullable|in:daily,never',
            'return_window_days'       => 'nullable|integer|min:0',
            'require_return_reason'    => 'nullable|boolean',
            'allow_partial_return'     => 'nullable|boolean',
            'auto_print_invoice'       => 'nullable|boolean',
            'show_product_image'       => 'nullable|boolean',
            'enable_hold_order'        => 'nullable|boolean',
            'allow_mixed_sale_types'   => 'nullable|boolean',
            'allow_price_change_in_cart' => 'nullable|boolean',
            'allow_price_below_minimum' => 'nullable|boolean',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updatePosSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updatePraSetting(Request $request)
    {
        $rules = [
            'enable_pra'          => 'required|boolean',
            'pra_code_prefix'     => 'required_if:enable_pra,1|nullable|string|max:50',
            'pra_registration_no' => 'required_if:enable_pra,1|nullable|string|max:100',
            'pra_api_key'         => 'required_if:enable_pra,1|nullable|string',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->all();
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updatePraSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateInventorySetting(Request $request)
    {
        $rules = [
            'low_stock_quantity' => 'nullable|integer|min:0',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        // Plain checkboxes (not selects) are omitted from the request entirely when
        // unchecked, so presence rather than value determines the boolean, matching the
        // convention already used for product toggles in ProductController::store().
        $obj = [
            'stock_tracking'     => $request->has('stock_tracking') ? 1 : 0,
            'negative_stock'     => $request->has('negative_stock') ? 1 : 0,
            'low_stock_alert'    => $request->has('low_stock_alert') ? 1 : 0,
            'low_stock_quantity' => $request->low_stock_quantity,
            'enable_batch_no'    => $request->has('enable_batch_no') ? 1 : 0,
            'enable_expiry_date' => $request->has('enable_expiry_date') ? 1 : 0,
        ];
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateInventorySetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateNotificationSetting(Request $request)
    {
        $rules = [
            'payment_due_days_before' => 'nullable|integer|min:0',
            'credit_limit_threshold_percent' => 'nullable|integer|min:1',
            'supplier_payment_reminder_days_before' => 'nullable|integer|min:0',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        // Plain checkboxes (not selects) are omitted from the request entirely when
        // unchecked, matching the convention used for every other setting tab.
        $obj = [
            'payment_due_alert_enabled'             => $request->has('payment_due_alert_enabled') ? 1 : 0,
            'payment_due_days_before'                => $request->payment_due_days_before,
            'credit_limit_alert_enabled'             => $request->has('credit_limit_alert_enabled') ? 1 : 0,
            'credit_limit_threshold_percent'         => $request->credit_limit_threshold_percent,
            'supplier_payment_reminder_enabled'      => $request->has('supplier_payment_reminder_enabled') ? 1 : 0,
            'supplier_payment_reminder_days_before'  => $request->supplier_payment_reminder_days_before,
            'order_status_alert_enabled'             => $request->has('order_status_alert_enabled') ? 1 : 0,
            'sound_enabled'                           => $request->has('sound_enabled') ? 1 : 0,
        ];
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateNotificationSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateBarcodeSetting(Request $request)
    {
        $rules = [
            'barcode_type'        => 'required|in:' . implode(',', array_keys(BarcodeType::getOptions())),
            'barcode_prefix'      => 'nullable|string|max:20',
            'code128_length'      => 'nullable|integer|min:4|max:48',
            'qr_data_source'      => 'required|in:' . implode(',', array_keys(QrDataSource::getOptions())),
            'qr_size_px'          => 'nullable|integer|min:50|max:1000',
            'qr_error_correction' => 'nullable|in:L,M,Q,H',
            'label_config'        => 'nullable|array',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'barcode_type',
            'barcode_prefix',
            'code128_length',
            'qr_data_source',
            'qr_size_px',
            'qr_error_correction',
            'label_config',
        ]);
        $obj['enable_barcode'] = $request->has('enable_barcode') ? 1 : 0;
        $obj['enable_qr_code'] = $request->has('enable_qr_code') ? 1 : 0;
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateBarcodeSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updatePrintSetting(Request $request)
    {
        $rules = [
            'header_config' => 'nullable|array',
            'footer_config' => 'nullable|array',
            'page_config'   => 'nullable|array',
            'body_config'   => 'nullable|array',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(['header_config', 'footer_config', 'page_config', 'body_config']);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updatePrintSetting($obj);

        if ($setting) {
            $this->print_setting_resolver->forgetCache($obj['business_id']);
        }

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function updateThermalPrintSetting(Request $request)
    {
        $rules = [
            'paper_width_mm' => 'nullable|integer|in:58,80',
            'field_config'   => 'nullable|array',
            'footer_config'  => 'nullable|array',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only(['paper_width_mm', 'field_config', 'footer_config']);
        $obj['is_enabled'] = $request->has('is_enabled') ? 1 : 0;
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;
        $obj['branch_id'] = $request->branch_id ?: null;

        $setting = $this->setting_service->updateThermalPrintSetting($obj);

        if ($setting) {
            $this->thermal_print_setting_resolver->forgetCache($obj['business_id'], $obj['branch_id']);
        }

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function previewThermalPrintSetting(Request $request)
    {
        $rules = [
            'paper_width_mm' => 'nullable|integer|in:58,80',
            'field_config'   => 'nullable|array',
            'footer_config'  => 'nullable|array',
        ];

        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $business_id = $request->business_id ?? Auth::user()->business_id;

        // Unsaved config built directly from the posted (not yet saved) form
        // values, so the preview always reflects the current form state -
        // never the persisted/cached setting.
        $thermal_config = new ThermalPrintConfig([
            'is_enabled' => true,
            'paper_width_mm' => $request->input('paper_width_mm', config('thermal_print_defaults.paper_width_mm')),
            'field_config' => array_merge(config('thermal_print_defaults.field_config'), $request->input('field_config', [])),
            'footer_config' => array_merge(config('thermal_print_defaults.footer_config'), $request->input('footer_config', [])),
        ]);

        $order = $this->resolveThermalPreviewOrder($business_id);

        $html = view('admin.order.print.thermal', compact('order', 'thermal_config'))->render();

        return $this->success(Message::FETCH, $html);
    }

    /**
     * Most recent real order for this business, so the preview reflects
     * genuine data whenever possible. Falls back to an in-memory (never
     * persisted) sample order for a business that has not made a sale yet,
     * so the preview never errors out on a brand-new business.
     */
    protected function resolveThermalPreviewOrder($business_id)
    {
        $order = Order::with([
            'business', 'branch', 'user', 'cashier', 'orderType', 'orderSource', 'voucher',
            'details', 'details.product', 'details.unit',
        ])
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->latest('date_created')
            ->first();

        if ($order) {
            return $order;
        }

        $business = \App\Models\Business::find($business_id);
        $branch = Auth::user()->branch ?? \App\Models\Branch::where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->first();

        $order = new Order([
            'order_id' => 'preview',
            'daily_order_id' => 'PREVIEW-0001',
            'business_id' => $business_id,
            'order_date' => now(),
            'sale_date' => now(),
            'subtotal' => 1250,
            'discount' => 10,
            'discount_amount' => 125,
            'tax' => 5,
            'tax_amount' => 56.25,
            'total' => 1181.25,
            'paid_amount' => 1181.25,
            'voucher_discount_amount' => 0,
        ]);

        $order->setRelation('business', $business);
        $order->setRelation('branch', $branch);
        $order->setRelation('user', new \App\Models\User(['name' => 'Walk-in Customer']));
        $order->setRelation('cashier', new \App\Models\User(['name' => Auth::user()->name ?? 'Cashier']));
        $order->setRelation('orderType', new \App\Models\OrderType(['name' => 'Dine In']));
        $order->setRelation('orderSource', new \App\Models\OrderSource(['name' => 'POS']));
        $order->setRelation('voucher', null);

        $detail_one = new \App\Models\OrderDetail(['quantity' => 2, 'unit_price' => 500, 'total' => 1000]);
        $detail_one->setRelation('product', new \App\Models\Product(['name' => 'Sample Product A']));
        $detail_one->setRelation('unit', new \App\Models\Unit(['name' => 'Pcs']));

        $detail_two = new \App\Models\OrderDetail(['quantity' => 1, 'unit_price' => 250, 'total' => 250]);
        $detail_two->setRelation('product', new \App\Models\Product(['name' => 'Sample Product B']));
        $detail_two->setRelation('unit', new \App\Models\Unit(['name' => 'Pcs']));

        $order->setRelation('details', collect([$detail_one, $detail_two]));
        $order->setRelation('payments', collect());

        return $order;
    }

    public function updateThemeSetting(Request $request)
    {
        $style_fields = [
            'sidebar_config.skin'               => 'in:dark,light,gradient',
            'sidebar_config.width'              => 'in:compact,default,wide',
            'sidebar_config.collapsed_behavior' => 'in:expanded,collapsed,hover',
            'sidebar_config.position'           => 'in:fixed,static,offcanvas',
            'header_config.style'               => 'in:light,dark,colored',
            'header_config.position'            => 'in:sticky,static',
            'header_config.type'                => 'in:detached,full',
            'content_config.background'         => 'in:default,light,dark',
            'content_config.spacing'            => 'in:comfortable,compact',
            'content_config.card_style'         => 'in:flat,shadow,bordered,gradient',
            'content_config.border_radius'      => 'in:none,sm,md,lg',
            'content_config.shadow_level'       => 'in:none,sm,md,lg',
            'content_config.table_style'        => 'in:default,striped,bordered,borderless,compact',
            'content_config.button_style'       => 'in:default,rounded,pill,square',
            'content_config.form_style'         => 'in:default,rounded,flat',
            'content_config.filter_style'       => 'in:compact,card,bordered,inline,collapsible',
            'content_config.content_display_style' => 'in:card,table,grid,dashboard',
            'content_config.animation_level'    => 'in:none,subtle,rich',
        ];

        $rules = array_merge([
            'preset'                  => 'nullable|string|max:50',
            'primary_color'           => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color'         => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'accent_color'            => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'font_family'             => 'nullable|string|max:100',
            'font_size_base'          => 'nullable|in:sm,md,lg',
            'sidebar_config'          => 'required|array',
            'header_config'           => 'required|array',
            'footer_config'           => 'required|array',
            'content_config'          => 'required|array',
            'footer_config.visible'   => 'required|boolean',
            'footer_config.sticky'    => 'required|boolean',
            'footer_config.style'     => 'in:light,dark,colored',
        ], $style_fields);

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $obj = $request->only([
            'preset',
            'primary_color',
            'secondary_color',
            'accent_color',
            'font_family',
            'font_size_base',
            'sidebar_config',
            'header_config',
            'footer_config',
            'content_config',
        ]);
        $obj['business_id'] = $request->business_id ?? Auth::user()->business_id;

        $setting = $this->setting_service->updateThemeSetting($obj);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }

    public function applyThemePreset(Request $request)
    {
        $rules = [
            'preset' => ['required', 'string', Rule::in(array_keys(config('theme_presets')))],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $business_id = $request->business_id ?? Auth::user()->business_id;
        $setting = $this->setting_service->applyThemePreset($business_id, $request->preset);

        return $setting
            ? $this->success(Message::UPDATE, $setting)
            : $this->error(Message::NOTUPDATE);
    }
}
