<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'package_id',
        'owner_name',
        'owner_email',
        'owner_phone',
        'code',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'logo',
        'status',
        'description',
        'subscription_start',
        'subscription_end',

        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
