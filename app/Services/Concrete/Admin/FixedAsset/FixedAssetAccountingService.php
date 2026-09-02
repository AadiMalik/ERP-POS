<?php

namespace App\Services\Concrete\Admin\FixedAsset;

use App\Enums\JournalSourceTypes;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Purchase;
use App\Services\Concrete\Admin\AccountingPeriodService;
use App\Services\Concrete\Admin\JournalEntryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Creates Fixed Asset JVs via configured Accounting Settings COA mappings.
 * Never hard-codes account IDs. Idempotent on source_type + source_id.
 */
class FixedAssetAccountingService
{
    public function getSettings(string $businessId): AccountingSetting
    {
        $setting = AccountingSetting::where('business_id', $businessId)->first();

        if (!$setting || !$setting->enable_accounting) {
            throw new Exception('Accounting is not enabled for this business. Please configure Accounting Settings.');
        }

        return $setting;
    }

    public function assertFixedAssetAccounts(AccountingSetting $setting): void
    {
        if (empty($setting->default_fixed_asset_account_id)) {
            throw new Exception('Fixed Asset Account is not configured in Accounting Settings.');
        }
        if (empty($setting->default_accumulated_depreciation_account_id)) {
            throw new Exception('Accumulated Depreciation Account is not configured in Accounting Settings.');
        }
        if (empty($setting->default_depreciation_expense_account_id)) {
            throw new Exception('Depreciation Expense Account is not configured in Accounting Settings.');
        }
    }

    public function purchaseAlreadyPosted(?string $purchaseId): bool
    {
        if (empty($purchaseId)) {
            return false;
        }

        return JournalEntry::where('source_type', JournalSourceTypes::PURCHASE)
            ->where('source_id', $purchaseId)
            ->where('is_deleted', 0)
            ->exists();
    }

    public function findExisting(string $sourceType, string $sourceId): ?JournalEntry
    {
        return JournalEntry::where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('is_deleted', 0)
            ->first();
    }

    protected function resolveJournal(string $short): Journal
    {
        $journal = Journal::where('short', $short)->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "' . $short . '" journal category found. Please configure it before posting fixed asset entries.');
        }

        return $journal;
    }

    protected function createEntry(array $header, array $lines): JournalEntry
    {
        $existing = $this->findExisting($header['source_type'], $header['source_id']);
        if ($existing) {
            return $existing;
        }

        app(AccountingPeriodService::class)->assertPostable($header['business_id'], $header['entry_date']);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $header['journal_id'],
            'business_id'      => $header['business_id'],
            'branch_id'        => $header['branch_id'] ?? null,
            'entry_no'         => generateJVNum($header['journal_id']),
            'reference_no'     => $header['reference_no'] ?? null,
            'entry_date'       => $header['entry_date'],
            'description'      => $header['description'] ?? null,
            'source_type'      => $header['source_type'],
            'source_id'        => $header['source_id'],
            'status'           => Status::POSTED,
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        foreach ($lines as $line) {
            if (((float) ($line['debit'] ?? 0)) == 0 && ((float) ($line['credit'] ?? 0)) == 0) {
                continue;
            }

            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id'        => $journal_entry->journal_entry_id,
                'account_id'              => $line['account_id'],
                'debit'                   => $line['debit'] ?? 0,
                'credit'                  => $line['credit'] ?? 0,
                'description'             => $line['description'] ?? null,
            ]);
        }

        JournalEntryService::assertBalanced($journal_entry->journal_entry_id);

        return $journal_entry;
    }

    /**
     * Dr Fixed Asset / Cr Cash|Bank|Supplier — skipped when purchase already posted.
     */
    public function postAcquisition(FixedAsset $asset): ?JournalEntry
    {
        if ($asset->accounting_from_purchase || $this->purchaseAlreadyPosted($asset->purchase_id)) {
            return null;
        }

        $existing = $this->findExisting(JournalSourceTypes::ASSET_ACQUISITION, $asset->fixed_asset_id);
        if ($existing) {
            return $existing;
        }

        $setting = $this->getSettings($asset->business_id);
        $this->assertFixedAssetAccounts($setting);

        $creditAccountId = $asset->payment_account_id
            ?: ($setting->default_cash_account_id ?: $setting->default_bank_account_id);

        if (empty($creditAccountId) && $asset->supplier_id) {
            $supplier = $asset->supplier ?: \App\Models\Supplier::find($asset->supplier_id);
            $creditAccountId = $supplier->account_id ?? $setting->default_supplier_account_id;
        }

        if (empty($creditAccountId)) {
            throw new Exception('No payment/credit account configured for asset acquisition. Set a Cash/Bank/Supplier account or select a payment account on the asset.');
        }

        $journal = $this->resolveJournal('FAV');
        $amount = (float) $asset->purchase_cost;
        $ref = $asset->asset_code ?: $asset->name;

        return $this->createEntry([
            'journal_id'   => $journal->journal_id,
            'business_id'  => $asset->business_id,
            'branch_id'    => $asset->branch_id,
            'reference_no' => $ref,
            'entry_date'   => Carbon::parse($asset->purchase_date)->toDateString(),
            'description'  => 'Fixed asset acquisition - ' . $ref,
            'source_type'  => JournalSourceTypes::ASSET_ACQUISITION,
            'source_id'    => $asset->fixed_asset_id,
        ], [
            [
                'account_id'  => $setting->default_fixed_asset_account_id,
                'debit'       => $amount,
                'credit'      => 0,
                'description' => 'Fixed asset acquisition - ' . $ref,
            ],
            [
                'account_id'  => $creditAccountId,
                'debit'       => 0,
                'credit'      => $amount,
                'description' => 'Fixed asset acquisition - ' . $ref,
            ],
        ]);
    }

    /**
     * Dr Depreciation Expense / Cr Accumulated Depreciation.
     * source_id = fixed_asset_depreciation_id for idempotency.
     */
    public function postDepreciation(FixedAsset $asset, FixedAssetDepreciation $depreciation): JournalEntry
    {
        $existing = $this->findExisting(JournalSourceTypes::ASSET_DEPRECIATION, $depreciation->fixed_asset_depreciation_id);
        if ($existing) {
            return $existing;
        }

        $setting = $this->getSettings($asset->business_id);
        $this->assertFixedAssetAccounts($setting);

        $journal = $this->resolveJournal('FDV');
        $amount = (float) $depreciation->depreciation_amount;
        $ref = ($asset->asset_code ?: $asset->name) . ' / ' . $depreciation->period_key;

        return $this->createEntry([
            'journal_id'   => $journal->journal_id,
            'business_id'  => $asset->business_id,
            'branch_id'    => $asset->branch_id,
            'reference_no' => $ref,
            'entry_date'   => Carbon::parse($depreciation->depreciation_date)->toDateString(),
            'description'  => 'Depreciation - ' . $ref,
            'source_type'  => JournalSourceTypes::ASSET_DEPRECIATION,
            'source_id'    => $depreciation->fixed_asset_depreciation_id,
        ], [
            [
                'account_id'  => $setting->default_depreciation_expense_account_id,
                'debit'       => $amount,
                'credit'      => 0,
                'description' => 'Depreciation expense - ' . $ref,
            ],
            [
                'account_id'  => $setting->default_accumulated_depreciation_account_id,
                'debit'       => 0,
                'credit'      => $amount,
                'description' => 'Accumulated depreciation - ' . $ref,
            ],
        ]);
    }

    /**
     * Disposal JV:
     * Dr Acc. Dep, Dr Cash/Loss (as needed), Cr Fixed Asset, Cr Gain (as needed).
     */
    public function postDisposal(FixedAsset $asset, float $salePrice, string $disposalType, ?string $proceedsAccountId = null): JournalEntry
    {
        $existing = $this->findExisting(JournalSourceTypes::ASSET_DISPOSAL, $asset->fixed_asset_id);
        if ($existing) {
            return $existing;
        }

        $setting = $this->getSettings($asset->business_id);
        $this->assertFixedAssetAccounts($setting);

        $cost = (float) $asset->purchase_cost;
        $accum = (float) $asset->accumulated_depreciation;
        $book = (float) $asset->current_book_value;
        $salePrice = round(max($salePrice, 0), 2);
        $gainLoss = round($salePrice - $book, 2);

        $journal = $this->resolveJournal('FXD');
        $ref = $asset->asset_code ?: $asset->name;
        $lines = [];

        // Remove accumulated depreciation
        if ($accum > 0) {
            $lines[] = [
                'account_id'  => $setting->default_accumulated_depreciation_account_id,
                'debit'       => $accum,
                'credit'      => 0,
                'description' => 'Clear accumulated depreciation - ' . $ref,
            ];
        }

        // Proceeds / receivable / cash
        if ($salePrice > 0) {
            $proceeds = $proceedsAccountId
                ?: ($setting->default_cash_account_id ?: $setting->default_bank_account_id);

            if (empty($proceeds)) {
                throw new Exception('Sale proceeds account is not configured. Select a Cash/Bank account or set defaults in Accounting Settings.');
            }

            $lines[] = [
                'account_id'  => $proceeds,
                'debit'       => $salePrice,
                'credit'      => 0,
                'description' => 'Asset sale proceeds - ' . $ref,
            ];
        }

        // Loss on disposal
        if ($gainLoss < 0) {
            if (empty($setting->default_loss_on_asset_disposal_account_id)) {
                throw new Exception('Loss on Asset Disposal Account is not configured in Accounting Settings.');
            }
            $lines[] = [
                'account_id'  => $setting->default_loss_on_asset_disposal_account_id,
                'debit'       => abs($gainLoss),
                'credit'      => 0,
                'description' => 'Loss on disposal - ' . $ref,
            ];
        }

        // Remove asset at cost
        $lines[] = [
            'account_id'  => $setting->default_fixed_asset_account_id,
            'debit'       => 0,
            'credit'      => $cost,
            'description' => 'Remove fixed asset - ' . $ref,
        ];

        // Gain on disposal
        if ($gainLoss > 0) {
            if (empty($setting->default_gain_on_asset_disposal_account_id)) {
                throw new Exception('Gain on Asset Disposal Account is not configured in Accounting Settings.');
            }
            $lines[] = [
                'account_id'  => $setting->default_gain_on_asset_disposal_account_id,
                'debit'       => 0,
                'credit'      => $gainLoss,
                'description' => 'Gain on disposal - ' . $ref,
            ];
        }

        return $this->createEntry([
            'journal_id'   => $journal->journal_id,
            'business_id'  => $asset->business_id,
            'branch_id'    => $asset->branch_id,
            'reference_no' => $ref,
            'entry_date'   => Carbon::parse($asset->disposal_date ?: now())->toDateString(),
            'description'  => 'Fixed asset disposal (' . $disposalType . ') - ' . $ref,
            'source_type'  => JournalSourceTypes::ASSET_DISPOSAL,
            'source_id'    => $asset->fixed_asset_id,
        ], $lines);
    }

    public function linkPurchaseReference(FixedAsset $asset): void
    {
        if (empty($asset->purchase_id)) {
            return;
        }

        $purchase = Purchase::find($asset->purchase_id);
        if ($purchase && $this->purchaseAlreadyPosted($asset->purchase_id)) {
            $asset->accounting_from_purchase = true;
            $je = JournalEntry::where('source_type', JournalSourceTypes::PURCHASE)
                ->where('source_id', $asset->purchase_id)
                ->where('is_deleted', 0)
                ->first();
            if ($je) {
                $asset->acquisition_journal_entry_id = $je->journal_entry_id;
            }
            $asset->save();
        }
    }
}
