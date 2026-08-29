<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroBusinessRegistration extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_business_registrations';
    protected $primaryKey = 'intro_business_registration_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'intro_business_registration_id',
        'business_id',
        'package_id',
        'billing_cycle',
        'business_name',
        'owner_name',
        'owner_email',
        'owner_phone',
        'business_email',
        'business_phone',
        'business_type',
        'city',
        'address',
        'notes',
        'status',
        'meta',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id', 'package_id');
    }
}
