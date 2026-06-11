<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'branch_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'branch_id',
        'business_id',
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

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
