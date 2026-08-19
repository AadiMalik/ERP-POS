<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'payslip_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payslip_id',
        'payroll_run_id',
        'employee_id',
        'basic_salary',
        'total_earnings',
        'total_deductions',
        'overtime_amount',
        'advance_deduction',
        'net_salary',
        'present_days',
        'absent_days',
        'leave_days',
        'status',
        'paid_at',
        'business_id',
        'branch_id',
        'createdby_id',
        'date_created',
        'date_updated',
    ];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id', 'payroll_run_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function items()
    {
        return $this->hasMany(PayslipItem::class, 'payslip_id', 'payslip_id');
    }
}
