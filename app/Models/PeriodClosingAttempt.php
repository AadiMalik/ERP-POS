<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodClosingAttempt extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'period_closing_attempt_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'period_closing_attempt_id',
        'accounting_period_id',
        'attempt_date',
        'trigger',
        'triggered_by_id',
        'result',
        'createdby_id',
        'date_created',
    ];

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id', 'accounting_period_id');
    }

    public function issues()
    {
        return $this->hasMany(PeriodClosingIssue::class, 'period_closing_attempt_id', 'period_closing_attempt_id');
    }

    public function triggeredBy()
    {
        return $this->belongsTo(User::class, 'triggered_by_id');
    }
}
