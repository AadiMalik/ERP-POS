<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPayment extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'customer_payment_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'customer_payment_id',
        'business_id',
        'branch_id',
        'user_id',
        'order_id',

        'payment_no',
        'payment_date',
        'payment_method',

        'payment_account_id',
        'customer_account_id',

        'reference_no',
        'cheque_date',

        'amount',
        'tax_amount',
        'discount_amount',
        'net_amount',

        'remarks',
        'attachment',

        'status',
        'postedby_id',
        'date_posted',

        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',

        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id', 'account_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function postedby()
    {
        return $this->belongsTo(User::class, 'postedby_id');
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
