<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WasteDamageExpiry extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'waste_damage_expiry_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'waste_damage_expiry_id',
        'business_id',
        'branch_id',
        'warehouse_id',
        'reference_no',
        'transaction_date',
        'reference',
        'notes',
        'total_quantity',
        'total_value',
        'status',
        'approvedby_id',
        'date_approved',

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

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function details()
    {
        return $this->hasMany(WasteDamageExpiryDetail::class, 'waste_damage_expiry_id', 'waste_damage_expiry_id');
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

    public function approvedby()
    {
        return $this->belongsTo(User::class, 'approvedby_id');
    }
}
