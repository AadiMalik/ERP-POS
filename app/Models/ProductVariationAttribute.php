<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationAttribute extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_variation_attribute_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_variation_attribute_id',
        'product_variation_id',
        'name',
        'value',
        'createdby_id',
        'date_created',
    ];

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id', 'user_id');
    }
}
