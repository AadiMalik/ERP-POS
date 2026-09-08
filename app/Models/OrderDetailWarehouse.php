<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetailWarehouse extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'order_detail_warehouse_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'order_detail_warehouse_id',
        'order_detail_id',
        'warehouse_id',
        'quantity',
        'base_quantity',
        'createdby_id',
        'date_created',
    ];

    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id', 'order_detail_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'warehouse_id');
    }
}
