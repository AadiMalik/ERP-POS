<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRecipeItem extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'product_recipe_item_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'product_recipe_item_id',
        'product_recipe_id',
        'raw_material_product_id',
        'raw_material_product_variation_id',
        'quantity',
        'unit_id',
        'warehouse_id',
        'is_deleted',
        'date_created',
        'date_updated',
    ];

    public function recipe()
    {
        return $this->belongsTo(ProductRecipe::class, 'product_recipe_id', 'product_recipe_id');
    }

    public function rawMaterialProduct()
    {
        return $this->belongsTo(Product::class, 'raw_material_product_id');
    }

    public function rawMaterialVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'raw_material_product_variation_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }
}
