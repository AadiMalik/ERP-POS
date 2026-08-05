<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariationStock extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'product_variation_stock_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'product_variation_stock_id',
        'business_id',
        'warehouse_id',
        'product_id',
        'product_variation_id',
        'avg_price',
        'quantity',
        'status',
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
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id', 'product_variation_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
