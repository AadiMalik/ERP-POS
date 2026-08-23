<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturnDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'order_return_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'order_return_detail_id',
        'order_return_id',
        'order_id',
        'order_detail_id',
        'product_id',
        'product_variation_id',
        'product_variation_unit_conversion_id',
        'unit_id',
        'ordered_quantity',
        'already_returned_quantity',
        'return_quantity',
        'conversion_factor',
        'base_quantity',
        'unit_price',
        'discount',
        'discount_amount',
        'voucher_id',
        'voucher_discount_amount',
        'free_quantity',
        'tax',
        'tax_amount',
        'subtotal',
        'total',
        'cost_price',
        'reason',
        'description',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class, 'order_return_id', 'order_return_id');
    }

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id', 'order_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function productVariationUnitConversion()
    {
        return $this->belongsTo(ProductVariationUnitConversion::class, 'product_variation_unit_conversion_id', 'product_variation_unit_conversion_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', 'unit_id');
    }
}
