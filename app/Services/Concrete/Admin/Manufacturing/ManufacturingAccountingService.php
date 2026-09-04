<?php

namespace App\Services\Concrete\Admin\Manufacturing;

use App\Enums\JournalSourceTypes;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\Production;
use App\Services\Concrete\Admin\AccountingPeriodService;
use App\Services\Concrete\Admin\JournalEntryService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

/**
 * Posts Production JVs via configured Accounting Settings COA mappings.
 * Mirrors FixedAssetAccountingService's shape exactly. Material-only
 * production is deliberately NOT posted here: raw-material value and
 * finished-goods value both sit in the same default_inventory_account_id
 * control account, so moving cost from raw materials to finished goods
 * within that one account nets to zero (identical to how a warehouse
 * Transfer posts no journal entry today) - a journal entry is only needed
 * when a Production capitalizes labor/overhead/other cost (postProductionCost())
 * or records abnormal wastage beyond the recipe's built-in wastage%
 * (postWastage()).
 */
class ManufacturingAccountingService
{
    public function getSettings(string $businessId): AccountingSetting
    {
        $setting = AccountingSetting::where('business_id', $businessId)->first();

        if (!$setting || !$setting->enable_accounting) {
            throw new Exception('Accounting is not enabled for this business. Please configure Accounting Settings.');
        }

        return $setting;
    }

    public function assertProductionAccounts(AccountingSetting $setting): void
    {
        if (empty($setting->default_inventory_account_id)) {
            throw new Exception('Inventory Account is not configured in Accounting Settings.');
        }
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
            throw new Exception('No "' . $short . '" journal category found. Please configure it before posting production entries.');
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
     * Capitalize a Production's labor/overhead/other cost into finished-goods
     * inventory value: Dr Inventory / Cr Expense. Skipped entirely (returns
     * null) when there's nothing beyond material cost to capitalize.
     */
    public function postProductionCost(Production $production): ?JournalEntry
    {
        $amount = (float) $production->labor_cost + (float) $production->overhead_cost + (float) $production->other_cost;
        if ($amount <= 0) {
            return null;
        }

        $setting = $this->getSettings($production->business_id);
        $this->assertProductionAccounts($setting);

        if (empty($setting->default_expense_account_id)) {
            throw new Exception('Default Expense Account is not configured in Accounting Settings - required to capitalize production labor/overhead/other cost.');
        }

        $journal = $this->resolveJournal('PCV');
        $ref = $production->production_no ?: $production->production_id;

        return $this->createEntry([
            'journal_id'   => $journal->journal_id,
            'business_id'  => $production->business_id,
            'branch_id'    => $production->branch_id,
            'reference_no' => $ref,
            'entry_date'   => Carbon::parse($production->manufacturing_date ?: now())->toDateString(),
            'description'  => 'Production cost capitalized - ' . $ref,
            'source_type'  => JournalSourceTypes::PRODUCTION_FINISHED_GOODS,
            'source_id'    => $production->production_id,
        ], [
            [
                'account_id'  => $setting->default_inventory_account_id,
                'debit'       => $amount,
                'credit'      => 0,
                'description' => 'Labor/overhead/other cost capitalized - ' . $ref,
            ],
            [
                'account_id'  => $setting->default_expense_account_id,
                'debit'       => 0,
                'credit'      => $amount,
                'description' => 'Labor/overhead/other cost capitalized - ' . $ref,
            ],
        ]);
    }

    /**
     * Abnormal wastage/loss recorded on a Production beyond the recipe's
     * built-in wastage% - Dr Stock Adjustment (expense) / Cr Inventory,
     * mirroring StockTakingService's loss treatment.
     */
    public function postWastage(Production $production, float $amount): ?JournalEntry
    {
        if ($amount <= 0) {
            return null;
        }

        $setting = $this->getSettings($production->business_id);
        $this->assertProductionAccounts($setting);

        if (empty($setting->default_stock_adjustment_account_id)) {
            throw new Exception('Default Stock Adjustment Account is not configured in Accounting Settings - required to post production wastage.');
        }

        $journal = $this->resolveJournal('PWV');
        $ref = $production->production_no ?: $production->production_id;

        return $this->createEntry([
            'journal_id'   => $journal->journal_id,
            'business_id'  => $production->business_id,
            'branch_id'    => $production->branch_id,
            'reference_no' => $ref,
            'entry_date'   => Carbon::parse($production->manufacturing_date ?: now())->toDateString(),
            'description'  => 'Production wastage - ' . $ref,
            'source_type'  => JournalSourceTypes::PRODUCTION_CONSUMPTION,
            'source_id'    => $production->production_id,
        ], [
            [
                'account_id'  => $setting->default_stock_adjustment_account_id,
                'debit'       => $amount,
                'credit'      => 0,
                'description' => 'Production wastage - ' . $ref,
            ],
            [
                'account_id'  => $setting->default_inventory_account_id,
                'debit'       => 0,
                'credit'      => $amount,
                'description' => 'Production wastage - ' . $ref,
            ],
        ]);
    }
}
