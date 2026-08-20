<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerProfile extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'customer_profile_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'customer_profile_id',
        'user_id',
        'business_id',
        'branch_id',
        'account_id',
        'legacy_customer_id',

        'code',

        'company_name',
        'contact_person',

        'address',
        'city',
        'state',
        'country',

        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_country',

        'credit_limit',
        'credit_days',
        'opening_balance',
        'opening_balance_type',
        'payment_terms',
        'notes',

        'is_walkin',
        'loyalty_points',

        'status',
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

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
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
