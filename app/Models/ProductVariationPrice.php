<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationPrice extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_variation_price_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_variation_price_id',
        'business_id',
        'product_variation_id',
        'sale_type_id',
        'price',
        'minimum_selling_price',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function saleType()
    {
        return $this->belongsTo(SaleType::class, 'sale_type_id', 'sale_type_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }
}
