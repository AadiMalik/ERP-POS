<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_variation_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_variation_id',
        'business_id',
        'product_id',
        'sku',
        'barcode',
        'name',
        'base_unit_id',
        'purchase_unit_id',
        'sale_unit_id',
        'purchase_price',
        'sale_price',
        'minimum_stock',
        'track_batch',
        'track_expiry',
        'is_deleted',
        'status',

        'createdby_id',
        'updatedby_id',
        'deletedby_id',

        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function attributes()
    {
        return $this->hasMany(ProductVariationAttribute::class, 'product_variation_id', 'product_variation_id');
    }

    public function productVariationUnitConversion() {
        return $this->hasMany(ProductVariationUnitConversion::class, 'product_variation_id', 'product_variation_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'base_unit_id', 'unit_id');
    }

    public function purchaseUnit()
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id', 'unit_id');
    }

    public function saleUnit()
    {
        return $this->belongsTo(Unit::class, 'sale_unit_id', 'unit_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id', 'user_id');
    }

    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id', 'user_id');
    }

    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id', 'user_id');
    }
}
