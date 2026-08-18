<?php

namespace App\Services\Concrete\Admin;

use App\Enums\BarcodeType;
use App\Enums\Filter;
use App\Enums\QrDataSource;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\BarcodeSetting;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\CustomerSetting;
use App\Models\EmailSetting;
use App\Models\FbrSetting;
use App\Models\InventorySetting;
use App\Models\NotificationSetting;
use App\Models\SmsSetting;
use App\Models\PosSetting;
use App\Models\PraSetting;
use App\Models\PrintSetting;
use App\Models\SupplierSetting;
use App\Models\ThemeSetting;
use App\Models\ThermalPrintSetting;
use App\Models\WhatsappSetting;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class SettingService
{
    protected $model_business;
    protected $model_business_setting;
    protected $model_accounting_setting;
    protected $model_customer_setting;
    protected $model_supplier_setting;
    protected $model_inventory_setting;
    protected $model_email_setting;
    protected $model_sms_setting;
    protected $model_whatsapp_setting;
    protected $model_fbr_setting;
    protected $model_print_setting;
    protected $model_barcode_setting;
    protected $model_theme_setting;
    protected $model_pos_setting;
    protected $model_pra_setting;
    protected $model_thermal_print_setting;
    protected $model_notification_setting;
    protected $expense_category_service;

    public function __construct(ExpenseCategoryService $expense_category_service)
    {
        $this->expense_category_service = $expense_category_service;
        $this->model_business = new Repository(new Business());
        $this->model_notification_setting = new Repository(new NotificationSetting());
        $this->model_business_setting = new Repository(new BusinessSetting());
        $this->model_accounting_setting = new Repository(new AccountingSetting());
        $this->model_customer_setting = new Repository(new CustomerSetting());
        $this->model_supplier_setting = new Repository(new SupplierSetting());
        $this->model_inventory_setting = new Repository(new InventorySetting());
        $this->model_email_setting = new Repository(new EmailSetting());
        $this->model_sms_setting = new Repository(new SmsSetting());
        $this->model_whatsapp_setting = new Repository(new WhatsappSetting());
        $this->model_fbr_setting = new Repository(new FbrSetting());
        $this->model_print_setting = new Repository(new PrintSetting());
        $this->model_barcode_setting = new Repository(new BarcodeSetting());
        $this->model_theme_setting = new Repository(new ThemeSetting());
        $this->model_pos_setting = new Repository(new PosSetting());
        $this->model_pra_setting = new Repository(new PraSetting());
        $this->model_thermal_print_setting = new Repository(new ThermalPrintSetting());
    }

    public function getBusinessSetting($business_id)
    {
        return $this->model_business_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getAccountingSetting($business_id)
    {
        return $this->model_accounting_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getCustomerSetting($business_id)
    {
        return $this->model_customer_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getSupplierSetting($business_id)
    {
        return $this->model_supplier_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getInventorySetting($business_id)
    {
        return $this->model_inventory_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getNotificationSetting($business_id)
    {
        // firstOrCreate() does not reflect DB column defaults back onto the in-memory
        // model after an insert (see getBarcodeSetting() above), so every default is
        // spelled out explicitly - otherwise a freshly created setting would render
        // as all-disabled/blank on the Notification Setting tab.
        return $this->model_notification_setting->getModel()::firstOrCreate(
            ['business_id' => $business_id],
            [
                'payment_due_alert_enabled' => true,
                'payment_due_days_before' => 3,
                'credit_limit_alert_enabled' => true,
                'credit_limit_threshold_percent' => 100,
                'supplier_payment_reminder_enabled' => true,
                'supplier_payment_reminder_days_before' => 3,
                'order_status_alert_enabled' => true,
                'sound_enabled' => true,
            ]
        );
    }

    public function getBarcodeSetting($business_id)
    {
        // firstOrCreate() does not reflect DB column defaults back onto the in-memory
        // model after an insert, so every default is spelled out explicitly here -
        // otherwise a freshly created setting would read as disabled/blank in PHP
        // even though the DB row itself has the correct defaults.
        return $this->model_barcode_setting->getModel()::firstOrCreate(
            ['business_id' => $business_id],
            [
                'enable_barcode' => true,
                'enable_qr_code' => true,
                'barcode_type' => BarcodeType::CODE128,
                'code128_length' => 12,
                'qr_data_source' => QrDataSource::INTERNAL_REFERENCE,
                'qr_size_px' => 200,
                'qr_error_correction' => 'M',
                'label_config' => config('barcode_label_defaults'),
                'date_created' => now(),
            ]
        );
    }

    public function getThemeSetting($business_id)
    {
        $defaults = config('theme_presets.sneat_default');

        return $this->model_theme_setting->getModel()::firstOrCreate(
            ['business_id' => $business_id],
            [
                'preset'          => 'sneat_default',
                'primary_color'   => $defaults['primary_color'],
                'secondary_color' => $defaults['secondary_color'],
                'accent_color'    => $defaults['accent_color'],
                'font_family'     => $defaults['font_family'],
                'font_size_base'  => $defaults['font_size_base'],
                'sidebar_config'  => $defaults['sidebar_config'],
                'header_config'   => $defaults['header_config'],
                'footer_config'   => $defaults['footer_config'],
                'content_config'  => $defaults['content_config'],
                'date_created'    => now(),
            ]
        );
    }

    public function getEmailSetting($business_id)
    {
        return $this->model_email_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getSmsSetting($business_id)
    {
        return $this->model_sms_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getFbrSetting($business_id)
    {
        return $this->model_fbr_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getPosSetting($business_id)
    {
        return $this->model_pos_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getPraSetting($business_id)
    {
        return $this->model_pra_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getWhatsappSetting($business_id)
    {
        return $this->model_whatsapp_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getPrintSetting($business_id, $document_type = 'default')
    {
        return $this->model_print_setting->getModel()::firstOrCreate(
            ['business_id' => $business_id, 'document_type' => $document_type],
            [
                'header_config' => config('print_defaults.header'),
                'footer_config' => config('print_defaults.footer'),
                'page_config' => config('print_defaults.page'),
                'body_config' => config('print_defaults.body'),
                'date_created' => now(),
            ]
        );
    }

    public function getThermalPrintSetting($business_id)
    {
        // firstOrCreate() does not reflect DB column defaults back onto the in-memory
        // model after an insert, so every default is spelled out explicitly here -
        // otherwise a freshly created setting would read as blank in PHP even though
        // the DB row itself has the correct defaults (same lesson as getBarcodeSetting()).
        return $this->model_thermal_print_setting->getModel()::firstOrCreate(
            ['business_id' => $business_id],
            [
                'is_enabled' => false,
                'paper_width_mm' => config('thermal_print_defaults.paper_width_mm'),
                'field_config' => config('thermal_print_defaults.field_config'),
                'footer_config' => config('thermal_print_defaults.footer_config'),
                'date_created' => now(),
            ]
        );
    }

    public function updateBusinessSetting(array $obj)
    {
        $model = $this->model_business_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id'],
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::user()->id;
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::user()->id;
        $setting->date_updated = now();
        $setting->save();

        session([
            'business_setting' => $setting->fresh()->toArray(),
        ]);

        return $setting;
    }

    public function updateAccountingSetting(array $obj)
    {
        $model = $this->model_accounting_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id'],
        ]);

        $old_expense_account_id = $setting->default_expense_account_id;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        // Keep every Expense Category that hasn't had its account manually
        // overridden pointed at the current default - categories with a
        // deliberate manual override (use_default_account = false) are left
        // untouched, and expenses already posted before this change keep
        // their own snapshot account_id, so historical JVs are unaffected.
        if ($setting->default_expense_account_id !== $old_expense_account_id) {
            $this->expense_category_service->syncDefaultAccount($obj['business_id'], $setting->default_expense_account_id);
        }

        return $setting;
    }

    public function updateCustomerSetting(array $obj)
    {
        $model = $this->model_customer_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateSupplierSetting(array $obj)
    {
        $model = $this->model_supplier_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateInventorySetting(array $obj)
    {
        $model = $this->model_inventory_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateNotificationSetting(array $obj)
    {
        $model = $this->model_notification_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateBarcodeSetting(array $obj)
    {
        $model = $this->model_barcode_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateEmailSetting(array $obj)
    {
        $model = $this->model_email_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateSmsSetting(array $obj)
    {
        $model = $this->model_sms_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateFbrSetting(array $obj)
    {
        $model = $this->model_fbr_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updatePosSetting(array $obj)
    {
        $model = $this->model_pos_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updatePraSetting(array $obj)
    {
        $model = $this->model_pra_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateWhatsappSetting(array $obj)
    {
        $model = $this->model_whatsapp_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updatePrintSetting(array $obj)
    {
        $model = $this->model_print_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id'],
            'document_type' => $obj['document_type'] ?? 'default',
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateThermalPrintSetting(array $obj)
    {
        $model = $this->model_thermal_print_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id'],
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        return $setting;
    }

    public function updateThemeSetting(array $obj)
    {
        $model = $this->model_theme_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();

        session([
            'theme_setting' => $setting->fresh()->toArray(),
        ]);

        return $setting;
    }

    public function applyThemePreset($business_id, $presetKey)
    {
        $preset = config("theme_presets.$presetKey");

        if (!$preset) {
            return false;
        }

        return $this->updateThemeSetting([
            'business_id'      => $business_id,
            'preset'           => $presetKey,
            'primary_color'    => $preset['primary_color'],
            'secondary_color'  => $preset['secondary_color'],
            'accent_color'     => $preset['accent_color'],
            'font_family'      => $preset['font_family'],
            'font_size_base'   => $preset['font_size_base'],
            'sidebar_config'   => $preset['sidebar_config'],
            'header_config'    => $preset['header_config'],
            'footer_config'    => $preset['footer_config'],
            'content_config'   => $preset['content_config'],
        ]);
    }
}
