<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSubscription extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'business_subscription_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'business_subscription_id',
        'business_id',
        'package_id',
        'start_at',
        'end_at',
        'subtotal',
        'discount',
        'discount_type',
        'discount_amount',
        'tax',
        'tax_amount',
        'total',
        'payment_status',
        'payment_method',
        'payment_reference',
        'status',

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

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
