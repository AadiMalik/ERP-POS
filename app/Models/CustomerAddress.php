<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'customer_address_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'customer_address_id',
        'user_id',
        'business_id',
        'label',
        'full_name',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'zip',
        'country',
        'is_default',
        'is_deleted',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }
}
