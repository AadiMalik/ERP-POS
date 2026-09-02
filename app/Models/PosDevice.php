<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosDevice extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $primaryKey = 'pos_device_id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'pos_device_id',
        'business_id',
        'branch_id',
        'warehouse_id',
        'pos_register_id',
        'name',
        'device_fingerprint',
        'api_token_hash',
        'status',
        'last_seen_at',
        'last_sync_at',
        'sync_cursors',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
        'is_deleted',
    ];

    protected $casts = [
        'sync_cursors' => 'array',
        'last_seen_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }
}
