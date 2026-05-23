<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'business_id',
        'supplier_code_prefix',
        'enable_credit_limit',
        'credit_limit',
        'default_payment_days',
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
