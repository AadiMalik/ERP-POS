<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'employee_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_id',
        'user_id',
        'employee_code',
        'department_id',
        'designation_id',
        'shift_id',
        'joining_date',
        'employment_type',
        'dob',
        'gender',
        'marital_status',
        'national_id',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'bank_name',
        'bank_account_title',
        'bank_account_number',
        'bank_branch_code',
        'payment_method',
        'photo',
        'status',
        'exit_date',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'designation_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'shift_id');
    }

    public function activeSalaryStructure()
    {
        return $this->hasOne(EmployeeSalaryStructure::class, 'employee_id', 'employee_id')
            ->where('status', 'active')
            ->where('is_deleted', 0);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class, 'employee_id', 'employee_id')->where('is_deleted', 0);
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
