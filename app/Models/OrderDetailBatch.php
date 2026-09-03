<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetailBatch extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'order_detail_batch_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'order_detail_batch_id',
        'order_detail_id',
        'product_variation_batch_id',
        'quantity',
        'base_quantity',
        'createdby_id',
        'date_created',
    ];

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id', 'order_detail_id');
    }

    public function productVariationBatch()
    {
        return $this->belongsTo(ProductVariationBatch::class, 'product_variation_batch_id', 'product_variation_batch_id');
    }
}
