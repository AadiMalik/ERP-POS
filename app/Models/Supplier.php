<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'supplier_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'supplier_id',
        'business_id',
        'branch_id',
        'account_id',

        'code',
        'name',

        'company_name',
        'contact_person',

        'email',
        'phone',
        'website',

        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'description',
        'image',

        'ntn',
        'strn',

        'credit_limit',
        'credit_days',
        'balance',

        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',

        'date_created',
        'date_updated',
        'date_deleted',
    ];

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
