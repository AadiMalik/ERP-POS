<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'accounting_period_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'accounting_period_id',
        'business_id',
        'fiscal_year_id',
        'name',
        'cadence',
        'start_date',
        'end_date',
        'status',
        'opened_at',
        'opened_by_id',
        'closed_at',
        'closed_by_id',
        'close_reason',
        'closed_automatically',
        'reopened_at',
        'reopened_by_id',
        'reopen_reason',
        'reopen_count',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id', 'fiscal_year_id');
    }

    public function closingAttempts()
    {
        return $this->hasMany(PeriodClosingAttempt::class, 'accounting_period_id', 'accounting_period_id');
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_id');
    }

    public function reopenedBy()
    {
        return $this->belongsTo(User::class, 'reopened_by_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
