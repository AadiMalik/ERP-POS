<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeLedgerEntry extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'employee_ledger_entry_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'employee_ledger_entry_id',
        'employee_id',
        'entry_date',
        'type',
        'reference_type',
        'reference_id',
        'debit',
        'credit',
        'balance_after',
        'description',
        'business_id',
        'createdby_id',
        'date_created',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}
