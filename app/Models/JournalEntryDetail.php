<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntryDetail extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'journal_entry_detail_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'journal_entry_detail_id',
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'user_id',
        'supplier_id',
        'bill_no',
        'cheque_no',
        'cheque_date',
        'description',
        'is_reconciled',
        'bank_reconciliation_id',
        'reconciled_at',
        'reconciled_by_id',
    ];

    protected $casts = [
        'is_reconciled' => 'boolean',
        'reconciled_at' => 'datetime',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function bankReconciliation()
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id', 'bank_reconciliation_id');
    }

    public function reconciledBy()
    {
        return $this->belongsTo(User::class, 'reconciled_by_id');
    }

    public function signedAmount(): float
    {
        return (float) $this->debit - (float) $this->credit;
    }
}
