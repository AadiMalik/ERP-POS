<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeExit extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'employee_exit_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_exit_id',
        'employee_id',
        'type',
        'request_date',
        'notice_period_days',
        'last_working_date',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'final_settlement_amount',
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

    public function clearances()
    {
        return $this->hasMany(ExitClearance::class, 'employee_exit_id', 'employee_exit_id');
    }
}
