<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'backup_setting_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'backup_setting_id',
        'is_scheduled_enabled',
        'frequency',
        'run_time',
        'day_of_week',
        'day_of_month',
        'retention_days',
        'max_storage_mb',
        'disks',
        'last_run_at',

        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'is_scheduled_enabled' => 'boolean',
        'disks' => 'array',
        'last_run_at' => 'datetime',
    ];

    public static function current(): self
    {
        return static::where('is_deleted', 0)->first() ?? new static([
            'is_scheduled_enabled' => false,
            'frequency' => 'daily',
            'run_time' => '02:00',
            'retention_days' => 30,
            'disks' => ['backups'],
        ]);
    }
}
