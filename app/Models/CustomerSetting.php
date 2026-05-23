<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'customer_code_prefix',
        'enable_credit_limit',
        'credit_limit',
        'loyalty_program',
        'loyalty_every_amount',
        'loyalty_point_rate',
        'loyalty_min_order_amount',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
