<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankStatementLine extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'bank_statement_line_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'bank_statement_line_id',
        'bank_reconciliation_id',
        'transaction_date',
        'amount',
        'reference',
        'description',
        'match_status',
        'matched_journal_entry_detail_id',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'float',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id', 'bank_reconciliation_id');
    }

    public function matchedJournalEntryDetail()
    {
        return $this->belongsTo(JournalEntryDetail::class, 'matched_journal_entry_detail_id', 'journal_entry_detail_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
