<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'order_return_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'order_return_id',
        'business_id',
        'branch_id',
        'warehouse_id',
        'customer_id',
        'order_id',
        'order_return_no',
        'order_return_date',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'refund_payment_method_id',
        'pos_register_session_id',
        'reason',
        'description',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function refundPaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'refund_payment_method_id');
    }

    public function orderReturnDetails()
    {
        return $this->hasMany(OrderReturnDetail::class, 'order_return_id', 'order_return_id');
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
