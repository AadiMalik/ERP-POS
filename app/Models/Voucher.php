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
        'value',
        'valid_from',
        'valid_to',
        'usage_limit_total',
        'usage_limit_per_customer',
        'used_count',
        'min_order_amount',
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
}
