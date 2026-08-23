<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'voucher_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'voucher_id',
        'business_id',
        'code',
        'name',
        'type',
        'promo_type',
        'value',
        'valid_from',
        'valid_to',
        'days_of_week',
        'time_start',
        'time_end',
        'usage_limit_total',
        'usage_limit_per_customer',
        'used_count',
        'min_order_amount',
        'max_discount_amount',
        'is_exclusive',
        'buy_quantity',
        'get_quantity',
        'get_discount_percent',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'voucher_products', 'voucher_id', 'product_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'voucher_categories', 'voucher_id', 'category_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'voucher_customers', 'voucher_id', 'user_id');
    }

    public function orderTypes()
    {
        return $this->belongsToMany(OrderType::class, 'voucher_order_types', 'voucher_id', 'order_type_id');
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'voucher_branches', 'voucher_id', 'branch_id');
    }

    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'voucher_brands', 'voucher_id', 'brand_id');
    }

    public function variations()
    {
        return $this->belongsToMany(ProductVariation::class, 'voucher_variations', 'voucher_id', 'product_variation_id');
    }

    public function saleTypes()
    {
        return $this->belongsToMany(SaleType::class, 'voucher_sale_types', 'voucher_id', 'sale_type_id');
    }

    public function orderSources()
    {
        return $this->belongsToMany(OrderSource::class, 'voucher_order_sources', 'voucher_id', 'order_source_id');
    }

    public function paymentMethods()
    {
        return $this->belongsToMany(PaymentMethod::class, 'voucher_payment_methods', 'voucher_id', 'payment_method_id');
    }

    public function getProducts()
    {
        return $this->belongsToMany(Product::class, 'voucher_get_products', 'voucher_id', 'product_id');
    }

    public function getCategories()
    {
        return $this->belongsToMany(Category::class, 'voucher_get_categories', 'voucher_id', 'category_id');
    }

    public function redemptions()
    {
        return $this->hasMany(VoucherRedemption::class, 'voucher_id', 'voucher_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
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

    /**
     * Human-readable rule label for POS/admin/order-detail display, e.g.
     * "10% off - Category: Beverages" or "Buy 2 Get 1 Free". Computed on the
     * fly from the already-loaded scope relations, never persisted.
     */
    public function describeRule(): string
    {
        if ($this->promo_type === 'bogo' || $this->promo_type === 'buy_x_get_y') {
            $free = (float) $this->get_discount_percent >= 100
                ? 'Free'
                : number_format((float) $this->get_discount_percent, 0) . '% Off';

            return sprintf('Buy %d Get %d %s', (int) $this->buy_quantity, (int) $this->get_quantity, $free);
        }

        $amount = $this->type === 'percent'
            ? number_format((float) $this->value, 2) . '%'
            : currency($this->value);

        $scope = $this->scopeLabel();

        return $scope ? "{$amount} off - {$scope}" : "{$amount} off";
    }

    /**
     * First matching configured scope dimension, for describeRule(). Relies on
     * the scope relations being eager-loaded (as VoucherService::$with does).
     */
    protected function scopeLabel(): ?string
    {
        if ($this->relationLoaded('products') && $this->products->isNotEmpty()) {
            return 'Product: ' . $this->products->pluck('name')->implode(', ');
        }

        if ($this->relationLoaded('categories') && $this->categories->isNotEmpty()) {
            return 'Category: ' . $this->categories->pluck('name')->implode(', ');
        }

        if ($this->relationLoaded('brands') && $this->brands->isNotEmpty()) {
            return 'Brand: ' . $this->brands->pluck('name')->implode(', ');
        }

        if ($this->relationLoaded('variations') && $this->variations->isNotEmpty()) {
            return 'Variation: ' . $this->variations->pluck('name')->implode(', ');
        }

        return null;
    }
}
