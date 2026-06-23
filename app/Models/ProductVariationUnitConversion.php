<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationUnitConversion extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_variation_unit_conversion_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_variation_unit_conversion_id',
        'business_id',
        'product_id',
        'product_variation_id',
        'from_unit_id',
        'to_unit_id',
        'conversion_factor',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id', 'unit_id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id', 'unit_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
