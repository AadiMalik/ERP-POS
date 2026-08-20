<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodClosingIssue extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'period_closing_issue_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'period_closing_issue_id',
        'period_closing_attempt_id',
        'accounting_period_id',
        'check_key',
        'source_type',
        'source_id',
        'summary',
        'date_created',
    ];

    public function attempt()
    {
        return $this->belongsTo(PeriodClosingAttempt::class, 'period_closing_attempt_id', 'period_closing_attempt_id');
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id', 'accounting_period_id');
    }
}
