<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructure extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'employee_salary_structure_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_salary_structure_id',
        'employee_id',
        'effective_from',
        'basic_salary',
        'overtime_rate_per_hour',
        'status',
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

    public function items()
    {
        return $this->hasMany(EmployeeSalaryStructureItem::class, 'employee_salary_structure_id', 'employee_salary_structure_id');
    }
}
