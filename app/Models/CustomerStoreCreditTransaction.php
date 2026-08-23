<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerStoreCreditTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'customer_store_credit_transaction_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'customer_store_credit_transaction_id',
        'business_id',
        'customer_id',
        'transaction_type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'description',
        'createdby_id',
        'date_created',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
