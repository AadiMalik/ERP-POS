<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExitClearance extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'exit_clearance_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'exit_clearance_id',
        'employee_exit_id',
        'area',
        'status',
        'cleared_by',
        'cleared_at',
        'remarks',
        'date_created',
    ];

    public function employeeExit()
    {
        return $this->belongsTo(EmployeeExit::class, 'employee_exit_id', 'employee_exit_id');
    }

    public function clearedBy()
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }
}
