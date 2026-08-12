<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpeningStockDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'opening_stock_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'opening_stock_detail_id',
        'opening_stock_id',
        'product_id',
        'product_variation_id',
        'product_variation_unit_conversion_id',
        'unit_id',
        'conversion_factor',
        'quantity',
        'base_quantity',
        'unit_cost',
        'total_value',
        'batch_no',
        'expiry_date',
        'product_variation_batch_id',
        'description',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function openingStock()
    {
        return $this->belongsTo(OpeningStock::class, 'opening_stock_id', 'opening_stock_id');
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

    public function productVariationBatch()
    {
        return $this->belongsTo(ProductVariationBatch::class, 'product_variation_batch_id', 'product_variation_batch_id');
    }
}
