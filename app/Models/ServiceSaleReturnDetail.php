<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSaleReturnDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'service_sale_return_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'service_sale_return_detail_id',
        'service_sale_return_id',
        'service_sale_id',
        'service_sale_detail_id',
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

    public function serviceSaleReturn()
    {
        return $this->belongsTo(ServiceSaleReturn::class, 'service_sale_return_id', 'service_sale_return_id');
    }

    public function serviceSaleDetail()
    {
        return $this->belongsTo(ServiceSaleDetail::class, 'service_sale_detail_id', 'service_sale_detail_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
}
