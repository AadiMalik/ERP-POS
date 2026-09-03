<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'bank_reconciliation_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'bank_reconciliation_id',
        'business_id',
        'branch_id',
        'account_id',
        'period_from',
        'period_to',
        'statement_opening_balance',
        'statement_closing_balance',
        'book_balance',
        'adjusted_book_balance',
        'difference',
        'status',
        'notes',
        'completed_at',
        'completed_by_id',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'statement_opening_balance' => 'float',
        'statement_closing_balance' => 'float',
        'book_balance' => 'float',
        'adjusted_book_balance' => 'float',
        'difference' => 'float',
        'completed_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id', 'account_id');
    }

    public function statementLines()
    {
        return $this->hasMany(BankStatementLine::class, 'bank_reconciliation_id', 'bank_reconciliation_id')
            ->where('is_deleted', 0);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by_id');
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

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
