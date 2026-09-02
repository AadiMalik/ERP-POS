<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $primaryKey = 'fixed_asset_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'fixed_asset_id',
        'business_id',
        'branch_id',
        'fixed_asset_category_id',
        'asset_code',
        'name',
        'description',
        'serial_number',
        'location',
        'purchase_date',
        'purchase_cost',
        'residual_value',
        'residual_percent',
        'min_book_value_percent',
        'useful_life_years',
        'depreciation_method',
        'depreciation_frequency',
        'depreciation_adjustment_mode',
        'depreciation_adjustment_rate',
        'supplier_id',
        'purchase_id',
        'accounting_from_purchase',
        'acquisition_journal_entry_id',
        'payment_account_id',
        'accumulated_depreciation',
        'current_book_value',
        'previous_book_value',
        'last_depreciation_amount',
        'last_depreciation_date',
        'next_depreciation_date',
        'depreciation_status',
        'disposal_date',
        'disposal_type',
        'disposal_reason',
        'sale_price',
        'disposal_journal_entry_id',
        'disposal_proceeds_account_id',
        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'last_depreciation_date' => 'date',
        'next_depreciation_date' => 'date',
        'disposal_date' => 'date',
        'accounting_from_purchase' => 'boolean',
        'purchase_cost' => 'decimal:2',
        'residual_value' => 'decimal:2',
        'current_book_value' => 'decimal:2',
        'previous_book_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'last_depreciation_amount' => 'decimal:2',
        'sale_price' => 'decimal:2',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function category()
    {
        return $this->belongsTo(FixedAssetCategory::class, 'fixed_asset_category_id', 'fixed_asset_category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'supplier_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id', 'purchase_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(Account::class, 'payment_account_id', 'account_id');
    }

    public function acquisitionJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'acquisition_journal_entry_id', 'journal_entry_id');
    }

    public function disposalJournalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'disposal_journal_entry_id', 'journal_entry_id');
    }

    public function depreciations()
    {
        return $this->hasMany(FixedAssetDepreciation::class, 'fixed_asset_id', 'fixed_asset_id')
            ->where('is_deleted', 0)
            ->orderBy('depreciation_date');
    }

    public function transactions()
    {
        return $this->hasMany(FixedAssetTransaction::class, 'fixed_asset_id', 'fixed_asset_id')
            ->orderByDesc('transaction_date')
            ->orderByDesc('date_created');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
}
