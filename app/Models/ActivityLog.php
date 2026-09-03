<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = 'activity_logs';
    protected $primaryKey = 'activity_log_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'activity_log_id',
        'business_id',
        'branch_id',
        'causer_id',
        'module',
        'record_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'date_created',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public static function prettifyLabel(string $value): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $value));
    }
}
