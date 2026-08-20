<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportBatch extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'import_batch_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'import_batch_id',
        'business_id',
        'branch_id',
        'module_key',
        'uploaded_by_id',
        'original_filename',
        'file_path',
        'status',
        'row_count',
        'valid_count',
        'invalid_count',
        'create_count',
        'update_count',
        'failed_count',
        'summary_json',
        'error_message',
        'is_deleted',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'summary_json' => 'array',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_id');
    }
}
