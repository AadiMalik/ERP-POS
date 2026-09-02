<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAssetDepreciation extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'fixed_asset_depreciation_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'fixed_asset_depreciation_id',
        'fixed_asset_id',
        'business_id',
        'branch_id',
        'period_key',
        'depreciation_date',
        'previous_value',
        'depreciation_amount',
        'new_value',
        'accumulated_depreciation',
        'journal_entry_id',
        'status',
        'source',
        'is_deleted',
        'createdby_id',
        'deletedby_id',
        'date_created',
        'date_deleted',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'previous_value' => 'decimal:2',
        'depreciation_amount' => 'decimal:2',
        'new_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
    ];

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id', 'fixed_asset_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id', 'journal_entry_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
