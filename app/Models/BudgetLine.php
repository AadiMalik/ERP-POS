<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'budget_line_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'budget_line_id',
        'budget_id',
        'account_id',
        'branch_id',
        'period_start',
        'period_end',
        'account_debit_normal',
        'budgeted_amount',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    public function budget()
    {
        return $this->belongsTo(Budget::class, 'budget_id', 'budget_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }
}
