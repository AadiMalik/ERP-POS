<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'leave_request_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'leave_request_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'attachment',
        'status',
        'approver_id',
        'approved_at',
        'remarks',
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

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id', 'leave_type_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
