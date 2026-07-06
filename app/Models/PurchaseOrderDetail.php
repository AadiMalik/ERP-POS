<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'purchase_order_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'purchase_order_detail_id',
        'purchase_order_id',
        'product_id',
        'product_variation_id',
        'ordered_quantity',
        'received_quantity',
        'rejected_quantity',
        'base_quantity',
        'unit_id',
        'conversion_factor',
        'unit_price',
        'total',
        'description',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

}
