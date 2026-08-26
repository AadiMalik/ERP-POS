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
use App\Models\WebsiteThemeSetting;
use App\Models\WhatsappSetting;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class SettingService
{
    use Auditable;

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
    protected $model_website_theme_setting;
    protected $model_pos_setting;
    protected $model_pra_setting;
    protected $model_thermal_print_setting;
    protected $model_notification_setting;
    protected $expense_category_service;
    protected $customer_service;
    protected $supplier_service;

    public function __construct(
        ExpenseCategoryService $expense_category_service,
        CustomerService $customer_service,
        SupplierService $supplier_service
    ) {
        $this->expense_category_service = $expense_category_service;
        $this->customer_service = $customer_service;
        $this->supplier_service = $supplier_service;
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
        $this->model_website_theme_setting = new Repository(new WebsiteThemeSetting());
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
        // This is always the business DEFAULT row (branch_id IS NULL) - see
        // getBranchThermalPrintSetting() for a branch-scoped override, which is
        // never auto-created (only the business default lazily creates itself).
        return $this->model_thermal_print_setting->getModel()::firstOrCreate(
            ['business_id' => $business_id, 'branch_id' => null],
            [
                'is_enabled' => false,
                'paper_width_mm' => config('thermal_print_defaults.paper_width_mm'),
                'field_config' => config('thermal_print_defaults.field_config'),
                'footer_config' => config('thermal_print_defaults.footer_config'),
                'date_created' => now(),
            ]
        );
    }

    /**
     * A specific branch's thermal print override, if one has been explicitly
     * saved for it - null if none exists (the caller falls back to
     * getThermalPrintSetting()'s business default). Deliberately does not
     * auto-create a row just because a branch was asked about.
     */
    public function getBranchThermalPrintSetting($business_id, $branch_id)
    {
        if (empty($branch_id)) {
            return null;
        }

        return $this->model_thermal_print_setting->getModel()::where('business_id', $business_id)
            ->where('branch_id', $branch_id)
            ->first();
    }

    /**
     * Logs an old/new snapshot of a business/POS/accounting setting change -
     * shared by every update*Setting() method below since they all follow
     * the same firstOrNew()/fill()/save() shape.
     */
    private function auditSetting(string $type, $setting, ?array $old_values): void
    {
        $this->logActivity(
            'setting',
            (string) ($setting->getKey() ?? $setting->business_id),
            $old_values === null ? 'created' : 'updated',
            $old_values,
            $setting->fresh()?->toArray(),
            ucfirst(str_replace('_', ' ', $type)) . ' setting ' . ($old_values === null ? 'created' : 'updated'),
            $setting->business_id ?? null
        );
    }

    public function updateBusinessSetting(array $obj)
    {
        $model = $this->model_business_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id'],
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::user()->id;
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::user()->id;
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('business', $setting, $old_values);

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

        $old_values = $setting->exists ? $setting->getOriginal() : null;
        $old_expense_account_id = $setting->default_expense_account_id;
        $old_customer_account_id = $setting->default_customer_account_id;
        $old_supplier_account_id = $setting->default_supplier_account_id;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('accounting', $setting, $old_values);

        // Keep every Expense Category that hasn't had its account manually
        // overridden pointed at the current default - categories with a
        // deliberate manual override (use_default_account = false) are left
        // untouched, and expenses already posted before this change keep
        // their own snapshot account_id, so historical JVs are unaffected.
        if ($setting->default_expense_account_id !== $old_expense_account_id) {
            $this->expense_category_service->syncDefaultAccount($obj['business_id'], $setting->default_expense_account_id);
        }

        // Mirror create-time attachment: when Customer/Supplier Account changes
        // in settings, push the new COA onto existing customer/supplier rows so
        // payments and credit sales can post without re-editing each record.
        if ($setting->default_customer_account_id !== $old_customer_account_id) {
            $this->customer_service->syncDefaultAccount($obj['business_id'], $setting->default_customer_account_id);
        }

        if ($setting->default_supplier_account_id !== $old_supplier_account_id) {
            $this->supplier_service->syncDefaultAccount($obj['business_id'], $setting->default_supplier_account_id);
        }

        return $setting;
    }

    public function updateCustomerSetting(array $obj)
    {
        $model = $this->model_customer_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('customer', $setting, $old_values);

        return $setting;
    }

    public function updateSupplierSetting(array $obj)
    {
        $model = $this->model_supplier_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('supplier', $setting, $old_values);

        return $setting;
    }

    public function updateInventorySetting(array $obj)
    {
        $model = $this->model_inventory_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('inventory', $setting, $old_values);

        return $setting;
    }

    public function updateNotificationSetting(array $obj)
    {
        $model = $this->model_notification_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('notification', $setting, $old_values);

        return $setting;
    }

    public function updateBarcodeSetting(array $obj)
    {
        $model = $this->model_barcode_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('barcode', $setting, $old_values);

        return $setting;
    }

    public function updateEmailSetting(array $obj)
    {
        $model = $this->model_email_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('email', $setting, $old_values);

        return $setting;
    }

    public function updateSmsSetting(array $obj)
    {
        $model = $this->model_sms_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('sms', $setting, $old_values);

        return $setting;
    }

    public function updateFbrSetting(array $obj)
    {
        $model = $this->model_fbr_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('fbr', $setting, $old_values);

        return $setting;
    }

    public function updatePosSetting(array $obj)
    {
        $model = $this->model_pos_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('pos', $setting, $old_values);

        return $setting;
    }

    public function updatePraSetting(array $obj)
    {
        $model = $this->model_pra_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('pra', $setting, $old_values);

        return $setting;
    }

    public function updateWhatsappSetting(array $obj)
    {
        $model = $this->model_whatsapp_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('whatsapp', $setting, $old_values);

        return $setting;
    }

    public function updatePrintSetting(array $obj)
    {
        $model = $this->model_print_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id'],
            'document_type' => $obj['document_type'] ?? 'default',
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('print', $setting, $old_values);

        return $setting;
    }

    public function updateThermalPrintSetting(array $obj)
    {
        $model = $this->model_thermal_print_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id'],
            'branch_id' => $obj['branch_id'] ?? null,
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('thermal_print', $setting, $old_values);

        return $setting;
    }

    public function updateThemeSetting(array $obj)
    {
        $model = $this->model_theme_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('theme', $setting, $old_values);

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

    public function getWebsiteThemeSetting($business_id)
    {
        $defaults = config('website_theme_presets.themes.theme1');

        return $this->model_website_theme_setting->getModel()::firstOrCreate(
            ['business_id' => $business_id],
            [
                'theme_preset'      => 'theme1',
                'primary_color'     => $defaults['colors']['primary'],
                'secondary_color'   => $defaults['colors']['secondary'],
                'accent_color'      => $defaults['colors']['accent'],
                'background_color'  => $defaults['colors']['background'],
                'surface_color'     => $defaults['colors']['surface'],
                'text_color'        => $defaults['colors']['text'],
                'heading_color'     => $defaults['colors']['heading'],
                'border_color'      => $defaults['colors']['border'],
                'success_color'     => $defaults['colors']['success'],
                'warning_color'     => $defaults['colors']['warning'],
                'error_color'       => $defaults['colors']['error'],
                'font_pairing'      => $defaults['font_pairing'],
                'font_size_base'    => $defaults['font_size_base'],
                'button_style'      => $defaults['button_style'],
                'typography_style'  => $defaults['typography_style'],
                'date_created'      => now(),
            ]
        );
    }

    public function updateWebsiteThemeSetting(array $obj)
    {
        $model = $this->model_website_theme_setting->getModel();

        $setting = $model::firstOrNew([
            'business_id' => $obj['business_id']
        ]);
        $old_values = $setting->exists ? $setting->getOriginal() : null;

        if (!$setting->exists) {
            $setting->createdby_id = Auth::id();
            $setting->date_created = now();
        }

        $setting->fill($obj);
        $setting->updatedby_id = Auth::id();
        $setting->date_updated = now();
        $setting->save();
        $this->auditSetting('website_theme', $setting, $old_values);

        return $setting;
    }

    public function applyWebsiteThemePreset($business_id, $presetKey)
    {
        $preset = config("website_theme_presets.themes.$presetKey");

        if (!$preset) {
            return false;
        }

        return $this->updateWebsiteThemeSetting([
            'business_id'       => $business_id,
            'theme_preset'      => $presetKey,
            'primary_color'     => $preset['colors']['primary'],
            'secondary_color'   => $preset['colors']['secondary'],
            'accent_color'      => $preset['colors']['accent'],
            'background_color'  => $preset['colors']['background'],
            'surface_color'     => $preset['colors']['surface'],
            'text_color'        => $preset['colors']['text'],
            'heading_color'     => $preset['colors']['heading'],
            'border_color'      => $preset['colors']['border'],
            'success_color'     => $preset['colors']['success'],
            'warning_color'     => $preset['colors']['warning'],
            'error_color'       => $preset['colors']['error'],
            'font_pairing'      => $preset['font_pairing'],
            'font_size_base'    => $preset['font_size_base'],
            'button_style'      => $preset['button_style'],
            'typography_style'  => $preset['typography_style'],
        ]);
    }

    /**
     * Build the full storefront-ready config (resolved font stacks, button
     * radius/weight/shadow, line-heights) served by the public website-theme
     * API. Falls back to theme1's own values for any unknown/missing key so
     * a bad row can never hand the storefront an invalid setting.
     */
    public function resolveWebsiteThemeConfig($setting)
    {
        $fontPairings = config('website_theme_presets.font_pairings');
        $buttonStyles = config('website_theme_presets.button_styles');
        $typographyStyles = config('website_theme_presets.typography_styles');
        $fontSizeScale = config('website_theme_presets.font_size_scale');
        $themes = config('website_theme_presets.themes');

        $themePreset = array_key_exists($setting->theme_preset, $themes) ? $setting->theme_preset : 'theme1';
        $pairing = $fontPairings[$setting->font_pairing] ?? $fontPairings['poppins_jakarta'];
        $button = $buttonStyles[$setting->button_style] ?? $buttonStyles['soft_pill'];
        $typography = $typographyStyles[$setting->typography_style] ?? $typographyStyles['comfortable'];
        $defaults = $themes['theme1']['colors'];

        return [
            'theme_preset' => $themePreset,
            'header_style' => $themePreset,
            'footer_style' => $themePreset,
            'colors' => [
                'primary'    => $setting->primary_color ?? $defaults['primary'],
                'secondary'  => $setting->secondary_color ?? $defaults['secondary'],
                'accent'     => $setting->accent_color ?? $defaults['accent'],
                'background' => $setting->background_color ?? $defaults['background'],
                'surface'    => $setting->surface_color ?? $defaults['surface'],
                'text'       => $setting->text_color ?? $defaults['text'],
                'heading'    => $setting->heading_color ?? $defaults['heading'],
                'border'     => $setting->border_color ?? $defaults['border'],
                'success'    => $setting->success_color ?? $defaults['success'],
                'warning'    => $setting->warning_color ?? $defaults['warning'],
                'error'      => $setting->error_color ?? $defaults['error'],
            ],
            'typography' => [
                'font_display'   => $pairing['font_display'],
                'font_body'      => $pairing['font_body'],
                'font_size_base' => $fontSizeScale[$setting->font_size_base] ?? '100%',
                'lh_heading'     => $typography['lh_heading'],
                'lh_body'        => $typography['lh_body'],
            ],
            'button' => [
                'radius' => $button['radius'],
                'weight' => $button['weight'],
                'shadow' => $button['shadow'],
            ],
        ];
    }

    /**
     * Public storefront "global settings" payload - business identity from
     * `businesses`, currency from `accounting_settings`, and website-only
     * config (favicon, SEO, social links, WhatsApp, hours) from
     * `website_theme_settings`. No data is duplicated into a new table.
     */
    public function getWebsitePublicSettings($business_id)
    {
        $business = $this->model_business->getModel()::find($business_id);
        $accounting = $this->getAccountingSetting($business_id);
        $website_setting = $this->getWebsiteThemeSetting($business_id);

        return $this->resolveWebsitePublicSettings($business, $accounting, $website_setting);
    }

    public function resolveWebsitePublicSettings($business, $accounting, $website_setting)
    {
        return [
            'business' => [
                'name'    => $business->name ?? null,
                'logo'    => $business && $business->logo ? asset('public/uploads/business/' . $business->logo) : null,
                'email'   => $business->email ?? null,
                'phone'   => $business->phone ?? null,
                'address' => $business->address ?? null,
                'city'    => $business->city ?? null,
                'state'   => $business->state ?? null,
                'country' => $business->country ?? null,
            ],
            'currency' => [
                'code'           => $accounting->currency ?? 'USD',
                'symbol'         => $accounting->currency_symbol ?? '$',
                'position'       => $accounting->currency_position ?? 'before',
                'decimal_points' => $accounting->decimal_points ?? 2,
            ],
            'seo' => [
                'title'       => $website_setting->seo_title ?? ($business->name ?? null),
                'description' => $website_setting->seo_description ?? null,
                'keywords'    => $website_setting->seo_keywords ?? null,
                'og_image'    => $website_setting->og_image ? asset('public/uploads/website/' . $website_setting->og_image) : null,
            ],
            'favicon'         => $website_setting->favicon ? asset('public/uploads/website/' . $website_setting->favicon) : null,
            'business_hours'  => $website_setting->business_hours ?? null,
            'whatsapp_number' => $website_setting->whatsapp_number ?? null,
            'free_delivery'   => [
                'enabled'    => (bool) ($website_setting->free_delivery_enabled ?? false),
                'min_amount' => $website_setting->free_delivery_min_amount !== null
                    ? (float) $website_setting->free_delivery_min_amount
                    : null,
            ],
            'bank_details' => [
                'bank_name' => $website_setting->bank_name ?? null,
                'account_title' => $website_setting->bank_account_title ?? null,
                'account_number' => $website_setting->bank_account_number ?? null,
                'iban' => $website_setting->bank_iban ?? null,
                'branch' => $website_setting->bank_branch ?? null,
                'swift_code' => $website_setting->bank_swift_code ?? null,
                'instructions' => $website_setting->bank_instructions ?? null,
            ],
        ];
    }
}
