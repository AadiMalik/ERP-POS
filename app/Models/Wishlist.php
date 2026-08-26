<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'wishlist_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'wishlist_id',
        'user_id',
        'business_id',
        'product_id',
        'product_variation_id',
        'item_key',
        'date_created',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    /**
     * Stable unique key for product-level vs variation-level wishlist rows.
     */
    public static function makeItemKey(string $product_id, ?string $product_variation_id = null): string
    {
        if ($product_variation_id) {
            return $product_id . ':' . $product_variation_id;
        }

        return $product_id;
    }
}
