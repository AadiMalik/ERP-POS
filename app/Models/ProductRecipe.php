<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Exactly one recipe per finished-good product variation (unique on
 * product_variation_id) - edited directly in place, never versioned.
 */
class ProductRecipe extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'product_recipe_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'product_recipe_id',
        'business_id',
        'product_id',
        'product_variation_id',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function items()
    {
        return $this->hasMany(ProductRecipeItem::class, 'product_recipe_id', 'product_recipe_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
