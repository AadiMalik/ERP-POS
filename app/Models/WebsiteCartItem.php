<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteCartItem extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'cart_item_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'cart_item_id',
        'cart_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'date_created',
        'date_updated',
    ];

    public function cart()
    {
        return $this->belongsTo(WebsiteCart::class, 'cart_id', 'cart_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }
}
