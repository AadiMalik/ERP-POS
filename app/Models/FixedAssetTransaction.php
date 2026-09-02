<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'fixed_asset_transaction_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'fixed_asset_transaction_id',
        'fixed_asset_id',
        'business_id',
        'branch_id',
        'transaction_type',
        'transaction_date',
        'description',
        'amount',
        'from_branch_id',
        'to_branch_id',
        'from_location',
        'to_location',
        'journal_entry_id',
        'reference_type',
        'reference_id',
        'meta',
        'createdby_id',
        'date_created',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id', 'fixed_asset_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id', 'journal_entry_id');
    }

    public function fromBranch()
    {
        return $this->belongsTo(Branch::class, 'from_branch_id', 'branch_id');
    }

    public function toBranch()
    {
        return $this->belongsTo(Branch::class, 'to_branch_id', 'branch_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
