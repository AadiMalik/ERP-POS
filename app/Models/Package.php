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
        'code',
        'description',
        'tagline',
        'badge',
        'best_for',
        'price',
        'discount',
        'price_yearly',
        'currency',
        'features',
        'limitations',
        'compare',
        'support',
        'cta',
        'is_custom',
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

    protected $casts = [
        'features' => 'array',
        'limitations' => 'array',
        'compare' => 'array',
        'is_custom' => 'boolean',
        'price' => 'float',
        'discount' => 'float',
        'price_yearly' => 'float',
    ];

    /**
     * List / catalogue price before package discount (PKR).
     */
    public function listPrice(): ?float
    {
        return $this->price !== null ? (float) $this->price : null;
    }

    public function discountPercent(): float
    {
        return max(0, min(100, (float) ($this->discount ?? 0)));
    }

    /**
     * Amount charged for this package period after discount %.
     * Each catalog row is period-specific (monthly or yearly via duration_type).
     */
    public function effectivePrice(): ?float
    {
        $list = $this->listPrice();
        if ($list === null) {
            return null;
        }

        $discount = $this->discountPercent();
        if ($discount <= 0) {
            return round($list, 2);
        }

        return round($list * (1 - ($discount / 100)), 2);
    }

    /**
     * Amount charged for a subscription period in PKR.
     * Packages are period-specific; $billingCycle is accepted for call-site
     * compatibility and should match duration_type.
     */
    public function priceForCycle(?string $billingCycle = null): ?float
    {
        return $this->effectivePrice();
    }

    /**
     * Monthly-equivalent display for yearly packages (effective annual ÷ 12).
     */
    public function monthlyEquivalent(): ?float
    {
        $effective = $this->effectivePrice();
        if ($effective === null) {
            return null;
        }

        if (($this->duration_type ?: 'monthly') === 'yearly') {
            return round($effective / 12, 2);
        }

        return $effective;
    }

    /** @deprecated Use monthlyEquivalent() — kept for older Intro map callers. */
    public function yearlyMonthlyEquivalent(): ?float
    {
        if (($this->duration_type ?: 'monthly') === 'yearly') {
            return $this->monthlyEquivalent();
        }

        if ($this->price_yearly === null) {
            return null;
        }

        return round(((float) $this->price_yearly) / 12, 2);
    }

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
