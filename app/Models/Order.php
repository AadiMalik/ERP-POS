<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'order_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'order_id',
        'daily_order_id',
        'business_id',
        'branch_id',
        'warehouse_id',
        'register_id',
        'register_session_id',
        'cashier_id',
        'user_id',
        'order_type_id',
        'order_source_id',
        'sale_type_id',
        'order_date',
        'sale_date',
        'subtotal',
        'discount',
        'discount_amount',
        'tax',
        'tax_amount',
        'total',
        'paid_amount',
        'change_amount',
        'discount_id',
        'voucher_id',
        'voucher_discount_amount',
        'notes',
        'due_date',
        'delivery_address',
        'payment_proof',
        'payment_confirmed_at',
        'payment_confirmed_by_id',
        'client_request_id',
        'pos_device_id',
        'offline_local_id',
        'status',
        'fbr_invoice_number',
        'fbr_status',
        'pra_invoice_number',
        'pra_status',
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
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'register_id');
    }

    public function registerSession()
    {
        return $this->belongsTo(PosRegisterSession::class, 'register_session_id');
    }

    public function posDevice()
    {
        return $this->belongsTo(PosDevice::class, 'pos_device_id', 'pos_device_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orderType()
    {
        return $this->belongsTo(OrderType::class, 'order_type_id');
    }

    public function orderSource()
    {
        return $this->belongsTo(OrderSource::class, 'order_source_id');
    }

    public function saleType()
    {
        return $this->belongsTo(SaleType::class, 'sale_type_id', 'sale_type_id');
    }

    public function discount()
    {
        return $this->belongsTo(Discount::class, 'discount_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'order_id');
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class, 'order_id', 'order_id')->where('is_deleted', 0);
    }

    public function customerPayments()
    {
        return $this->hasMany(CustomerPayment::class, 'order_id', 'order_id')->where('is_deleted', 0);
    }

    public function statusHistory()
    {
        return $this->hasMany(OrderStatusHistory::class, 'order_id', 'order_id');
    }

    public function orderReturns()
    {
        return $this->hasMany(OrderReturn::class, 'order_id', 'order_id')->where('is_deleted', 0);
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

    public function paymentConfirmedBy()
    {
        return $this->belongsTo(User::class, 'payment_confirmed_by_id');
    }

    public function getPaymentProofUrlAttribute()
    {
        if (empty($this->payment_proof)) {
            return null;
        }

        return asset('public/uploads/order_payment_proof/' . $this->payment_proof);
    }
}
