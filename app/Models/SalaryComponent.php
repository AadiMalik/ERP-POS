<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'salary_component_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'salary_component_id',
        'name',
        'code',
        'type',
        'calculation_type',
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

    public function structureItems()
    {
        return $this->hasMany(EmployeeSalaryStructureItem::class, 'salary_component_id', 'salary_component_id');
    }
}
