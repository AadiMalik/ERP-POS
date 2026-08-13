<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'order_payment_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'order_payment_id',
        'order_id',
        'payment_method_id',
        'amount',
        'reference_no',
        'is_deleted',
        'createdby_id',
        'date_created',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}
