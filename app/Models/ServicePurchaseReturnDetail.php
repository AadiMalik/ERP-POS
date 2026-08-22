<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServicePurchaseReturnDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'service_purchase_return_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'service_purchase_return_detail_id',
        'service_purchase_return_id',
        'service_purchase_id',
        'service_purchase_detail_id',
        'product_id',
        'item_name',
        'quantity',
        'already_returned_quantity',
        'return_quantity',
        'unit_price',
        'discount',
        'discount_amount',
        'tax',
        'tax_amount',
        'subtotal',
        'total',
        'reason',
        'description',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function servicePurchaseReturn()
    {
        return $this->belongsTo(ServicePurchaseReturn::class, 'service_purchase_return_id', 'service_purchase_return_id');
    }

    public function servicePurchaseDetail()
    {
        return $this->belongsTo(ServicePurchaseDetail::class, 'service_purchase_detail_id', 'service_purchase_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
