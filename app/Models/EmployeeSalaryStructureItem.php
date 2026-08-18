<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryStructureItem extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'employee_salary_structure_item_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_salary_structure_item_id',
        'employee_salary_structure_id',
        'salary_component_id',
        'amount_or_percentage',
    ];

    public function component()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id', 'salary_component_id');
    }
}
