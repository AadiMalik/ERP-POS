<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'account_type_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'account_type_id',
        'name',
        'code',
        'description',
        'business_id',
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
        return $this->belongsTo(Business::class);
    }

    public function accountSubTypes()
    {
        return $this->hasMany(AccountSubType::class, 'account_type_id', 'account_type_id');
    }

    public function accounts()
    {
        return $this->hasMany(Account::class, 'account_type_id', 'account_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
