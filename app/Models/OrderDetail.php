<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'order_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'order_detail_id',
        'order_id',
        'product_id',
        'product_variation_id',
        'product_variation_unit_conversion_id',
        'product_variation_batch_id',
        'unit_id',
        'quantity',
        'conversion_factor',
        'base_quantity',
        'unit_price',
        'sale_type_id',
        'final_unit_price',
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
        'notes',
        'serial_numbers',
        'createdby_id',
        'date_created',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function unitConversion()
    {
        return $this->belongsTo(ProductVariationUnitConversion::class, 'product_variation_unit_conversion_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function saleType()
    {
        return $this->belongsTo(SaleType::class, 'sale_type_id', 'sale_type_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id', 'voucher_id');
    }

    public function productVariationBatch()
    {
        return $this->belongsTo(ProductVariationBatch::class, 'product_variation_batch_id', 'product_variation_batch_id');
    }

    public function orderDetailBatches()
    {
        return $this->hasMany(OrderDetailBatch::class, 'order_detail_id', 'order_detail_id');
    }
}
