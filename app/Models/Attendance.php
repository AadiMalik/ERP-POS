<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'attendance_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'attendance_id',
        'employee_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'working_hours',
        'late_minutes',
        'early_leave_minutes',
        'source',
        'notes',
        'business_id',
        'branch_id',
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

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
