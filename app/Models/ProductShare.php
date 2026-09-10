<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per "share this product" click from the website or mobile app -
 * see App\Services\Concrete\Api\ProductShareService::record(). Append-only:
 * never updated or deleted, so no date_updated/date_deleted/is_deleted.
 */
class ProductShare extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'product_share_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_share_id',
        'business_id',
        'product_id',
        'customer_id',
        'platform',
        'date_created',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id', 'id');
    }
}
