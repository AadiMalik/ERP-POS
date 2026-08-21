<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationPriceHistory extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_variation_price_history_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_variation_price_history_id',
        'business_id',
        'product_variation_id',
        'sale_type_id',
        'old_price',
        'new_price',
        'changedby_id',
        'date_created',
    ];

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function saleType()
    {
        return $this->belongsTo(SaleType::class, 'sale_type_id', 'sale_type_id');
    }

    public function changedby()
    {
        return $this->belongsTo(User::class, 'changedby_id');
    }
}
