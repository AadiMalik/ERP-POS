<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationSerialNumber extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_variation_serial_number_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_variation_serial_number_id',
        'business_id',
        'branch_id',
        'product_id',
        'product_variation_id',
        'warehouse_id',
        'serial_no',
        'status',
        'avg_price',
        'source_reference_type',
        'source_reference_id',
        'source_detail_id',
        'current_transfer_note_detail_id',
        'current_order_id',
        'current_order_detail_id',
        'current_customer_id',
        'warranty_expires_at',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'current_order_id', 'order_id');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'current_order_detail_id', 'order_detail_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'current_customer_id');
    }

    public function movements()
    {
        return $this->hasMany(ProductVariationSerialMovement::class, 'product_variation_serial_number_id', 'product_variation_serial_number_id')
            ->orderBy('date_created', 'desc');
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
