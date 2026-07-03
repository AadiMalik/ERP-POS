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
        'customer_id',
        'supplier_id',
        'bill_no',
        'cheque_no',
        'cheque_date',
        'description'
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    // public function supplier()
    // {
    //     return $this->belongsTo(Supplier::class, 'supplier_id');
    // }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
