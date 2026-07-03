<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'journal_entry_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'journal_entry_id',
        'journal_id',
        'business_id',
        'branch_id',
        'entry_no',
        'entry_date',
        'reference_no',
        'description',
        'source_type',
        'source_id',
        'status',
        'postedby_id',
        'date_posted',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function journal()
    {
        return $this->belongsTo(Journal::class, 'journal_id', 'journal_id');
    }

    public function journalEntryDetails()
    {
        return $this->hasMany(JournalEntryDetail::class, 'journal_entry_id', 'journal_entry_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'branch_id');
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
