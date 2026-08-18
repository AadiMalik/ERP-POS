<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAdvance extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'employee_advance_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_advance_id',
        'employee_id',
        'amount',
        'reason',
        'request_date',
        'status',
        'approved_by',
        'approved_at',
        'installments_count',
        'installment_amount',
        'remaining_balance',
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

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
