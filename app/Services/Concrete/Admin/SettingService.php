<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\CustomerSetting;
use App\Models\EmailSetting;
use App\Models\FbrSetting;
use App\Models\InventorySetting;
use App\Models\SmsSetting;
use App\Models\PrintSetting;
use App\Models\SupplierSetting;
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

    public function __construct()
    {
        $this->model_business = new Repository(new Business());
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

    public function getWhatsappSetting($business_id)
    {
        return $this->model_whatsapp_setting->getModel()::firstOrCreate(['business_id' => $business_id]);
    }

    public function getPrintSetting($business_id)
    {
        return $this->model_print_setting->getModel()::firstOrCreate(
            ['business_id' => $business_id],
            [
                'header_config' => config('print_defaults.header'),
                'footer_config' => config('print_defaults.footer'),
                'page_config' => config('print_defaults.page'),
                'body_config' => config('print_defaults.body'),
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

    public function updateInventorySetting($obj)
    {
        $obj['updatedby_id'] = Auth::user()->id;
        $obj['date_updated'] = now();
        return $this->model_inventory_setting->update($obj, $obj['business_id']);
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
}
