<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'login_histories';
    protected $primaryKey = 'login_history_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'login_history_id',
        'user_id',
        'business_id',
        'branch_id',
        'email',
        'ip_address',
        'user_agent',
        'device',
        'status',
        'login_at',
        'logout_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
