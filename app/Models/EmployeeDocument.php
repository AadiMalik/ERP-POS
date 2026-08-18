<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDocument extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'employee_document_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_document_id',
        'employee_id',
        'document_type',
        'file_name',
        'file_path',
        'expiry_date',
        'notes',
        'business_id',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
