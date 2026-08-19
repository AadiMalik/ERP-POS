<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'package_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'package_id',
        'name',
        'description',
        'price',
        'order',
        'duration_type',
        'duration_days',
        'trial_days',
        'status',
        'max_branches',
        'max_users',
        'max_customers',
        'max_warehouses',
        'max_categories',
        'max_products',
        'max_suppliers',
        'max_purchase_orders',
        'max_purchases',
        'max_sales',
        'max_transfers',
        'max_expenses',
        'max_vouchers',
        'is_pos_enabled',
        'is_inventory_enabled',
        'is_accounting_enabled',
        'is_hrm_enabled',
        'is_payroll_enabled',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }

    public function modules()
    {
        return $this->hasMany(PackageModule::class, 'package_id', 'package_id');
    }

    /**
     * Whether the given SubscriptionModuleRegistry module key is enabled on
     * this package. Relies on `modules` being loaded (eager-load with
     * `Package::with('modules')` at the call site to avoid N+1).
     */
    public function moduleEnabled(string $moduleKey): bool
    {
        $module = $this->modules->firstWhere('module_key', $moduleKey);

        return $module ? $module->is_enabled : false;
    }

    /**
     * The configured limit for the given module key, or null when unlimited
     * or when the module has no package_modules row.
     */
    public function moduleLimit(string $moduleKey): ?int
    {
        $module = $this->modules->firstWhere('module_key', $moduleKey);

        if (!$module || $module->is_unlimited) {
            return null;
        }

        return $module->limit_value;
    }

    public function moduleIsUnlimited(string $moduleKey): bool
    {
        $module = $this->modules->firstWhere('module_key', $moduleKey);

        return $module ? (bool) $module->is_unlimited : false;
    }
}
