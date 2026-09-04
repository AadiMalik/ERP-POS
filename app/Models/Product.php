<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_id',
        'business_id',
        'category_id',
        'sub_category_id',
        'brand_id',
        'name',
        'slug',
        'type',
        'usage_type',
        'is_purchasable',
        'is_sellable',
        'is_raw_material',
        'is_manufactured',
        'is_track_stock',
        'is_pos_visible',
        'is_website_visible',
        'is_app_visible',
        'short_description',
        'description',
        'is_featured',
        'is_trending',
        'is_best_seller',
        'is_loyalty_enabled',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function productVariations() {
        return $this->hasMany(ProductVariation::class, 'product_id', 'product_id')
        ->with('attributes');
    }

    public function productImages() {
        return $this->hasMany(ProductImage::class, 'product_id', 'product_id');
    }

    public function productFeatures() {
        return $this->hasMany(ProductFeature::class, 'product_id', 'product_id');
    }

    public function business() {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function category() {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function subCategory() {
        return $this->belongsTo(SubCategory::class, 'sub_category_id', 'sub_category_id');
    }

    public function brand() {
        return $this->belongsTo(Brand::class, 'brand_id', 'brand_id');
    }

    public function createdby() {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedby() {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby() {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
