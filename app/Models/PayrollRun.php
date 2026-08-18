<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'payroll_run_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'payroll_run_id',
        'month',
        'year',
        'status',
        'total_amount',
        'generated_at',
        'finalized_at',
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

    public function payslips()
    {
        return $this->hasMany(Payslip::class, 'payroll_run_id', 'payroll_run_id');
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
