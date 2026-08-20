<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodClosingRule extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'period_closing_rule_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'period_closing_rule_id',
        'business_id',
        'check_unposted_journal_entries',
        'check_pending_purchase_returns',
        'check_pending_leave_requests',
        'check_pending_employee_advances',
        'check_pending_employee_exits',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
