<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDeduction extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'employee_deduction_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_deduction_id',
        'employee_id',
        'title',
        'amount',
        'is_recurring',
        'effective_from',
        'effective_to',
        'business_id',
        'status',
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
