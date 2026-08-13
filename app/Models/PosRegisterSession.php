<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosRegisterSession extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'pos_register_session_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'pos_register_session_id',
        'pos_register_id',
        'business_id',
        'branch_id',
        'cashier_id',
        'opening_datetime',
        'opening_cash',
        'opening_notes',
        'closing_datetime',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'closing_notes',
        'status',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function cashMovements()
    {
        return $this->hasMany(PosRegisterCashMovement::class, 'pos_register_session_id');
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
