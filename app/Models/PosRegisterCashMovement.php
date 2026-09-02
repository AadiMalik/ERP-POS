<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosRegisterCashMovement extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'pos_register_cash_movement_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'pos_register_cash_movement_id',
        'pos_register_session_id',
        'offline_local_id',
        'type',
        'amount',
        'reason',
        'is_deleted',
        'createdby_id',
        'date_created',
    ];

    public function session()
    {
        return $this->belongsTo(PosRegisterSession::class, 'pos_register_session_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
