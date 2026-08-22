<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceSaleDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'service_sale_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'service_sale_detail_id',
        'service_sale_id',
        'product_id',
        'item_name',
        'quantity',
        'unit_price',
        'discount',
        'discount_amount',
        'tax',
        'tax_amount',
        'subtotal',
        'total',
        'description',

        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
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
