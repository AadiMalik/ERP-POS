<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'backup_log_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'backup_log_id',
        'type',
        'status',
        'disk',
        'file_path',
        'file_name',
        'size_bytes',
        'checksum_sha256',
        'includes_database',
        'includes_files',
        'error_message',
        'started_at',
        'finished_at',
        'initiated_by',
        'is_deleted',
        'date_created',
    ];

    protected $casts = [
        'includes_database' => 'boolean',
        'includes_files' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }
}
