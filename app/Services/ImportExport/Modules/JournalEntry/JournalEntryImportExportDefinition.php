<?php

namespace App\Services\ImportExport\Modules\JournalEntry;

use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Services\Concrete\Admin\JournalEntryService;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ChildTableDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

class JournalEntryImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'journal-entry';
    }

    public function label(): string
    {
        return 'Journal Entries';
    }

    public function modelClass(): string
    {
        return JournalEntry::class;
    }

    public function primaryKey(): string
    {
        return 'journal_entry_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function groupKeyColumn(): ?string
    {
        return 'Entry No';
    }

    public function childRelationName(): ?string
    {
        return 'journalEntryDetails';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Entry No',
                attribute: 'entry_no',
                type: 'string',
                required: true,
                sampleValues: ['JV-0001', 'JV-0002'],
                exportAccessor: 'entry_no',
                notes: 'Repeat this exact value on every line item belonging to the same journal entry.',
            ),
            new ColumnDefinition(
                key: 'Entry Date',
                attribute: 'entry_date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-20', '2026-08-21'],
                exportAccessor: 'entry_date',
            ),
            new ColumnDefinition(
                key: 'Journal',
                attribute: 'journal_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Journal::class, 'journal', 'name', scopeToBusiness: false),
                sampleValues: ['General Journal', 'General Journal'],
                exportAccessor: fn ($m) => $m->journal->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Description',
                attribute: 'description',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'description',
            ),
        ];
    }

    public function childDefinition(): ?ChildTableDefinition
    {
        return new ChildTableDefinition(
            modelClass: JournalEntryDetail::class,
            primaryKey: 'journal_entry_detail_id',
            foreignKeyAttribute: 'journal_entry_id',
            columns: [
                new ColumnDefinition(
                    key: 'Account',
                    attribute: 'account_id',
                    type: 'relation',
                    required: true,
                    relation: new RelationSpec(Account::class, 'account', 'name', scopeToBusiness: true),
                    sampleValues: ['Cash', 'Sales Revenue'],
                    notes: 'Copy the exact Account Name from the Chart of Accounts.',
                ),
                new ColumnDefinition(
                    key: 'Debit',
                    attribute: 'debit',
                    type: 'decimal',
                    required: false,
                    sampleValues: ['100', '0'],
                ),
                new ColumnDefinition(
                    key: 'Credit',
                    attribute: 'credit',
                    type: 'decimal',
                    required: false,
                    sampleValues: ['0', '100'],
                ),
                new ColumnDefinition(
                    key: 'Description',
                    attribute: 'description',
                    type: 'string',
                    required: false,
                    sampleValues: ['', ''],
                ),
            ],
            minChildren: 2,
        );
    }

    public function uniqueKeyColumns(): array
    {
        return ['entry_no'];
    }

    /**
     * Mirrors the cross-field checks JournalEntryController::store() runs via
     * $validator->after(): total debit must equal total credit across the
     * entry's lines, and each line must have exactly one of debit/credit set
     * (not both, not neither).
     */
    protected function applyDomainValidation(ResolvedRow $row, ImportContext $ctx): void
    {
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($row->children as $child) {
            if ($child->action === 'invalid') {
                continue;
            }

            $debit = (float) ($child->attributes['debit'] ?? 0);
            $credit = (float) ($child->attributes['credit'] ?? 0);

            if ($debit == 0 && $credit == 0) {
                $child->action = 'invalid';
                $child->errors[] = ['column' => 'Debit', 'value' => null, 'reason' => 'Either Debit or Credit is required on this line.'];
                continue;
            }

            if ($debit > 0 && $credit > 0) {
                $child->action = 'invalid';
                $child->errors[] = ['column' => 'Debit', 'value' => null, 'reason' => 'Both Debit and Credit cannot have values on the same line.'];
                continue;
            }

            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        if ($row->action !== 'invalid' && abs($totalDebit - $totalCredit) > 0.0001) {
            $row->action = 'invalid';
            $row->errors[] = [
                'column' => $this->groupKeyColumn(),
                'value' => $row->groupKey,
                'reason' => "Total Debit ({$totalDebit}) and Total Credit ({$totalCredit}) must be equal.",
            ];
        }
    }

    /**
     * Mirrors JournalEntryService::save(), except the created/updated entry
     * is always left in 'pending' (unposted) status regardless of the
     * service's own posted-by-default behavior - posting a journal entry
     * stays a separate, manual, permission-gated action
     * (journal-entry.post) so import can never silently post to the ledger.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $attributes = [
            'journal_id' => $row->attributes['journal_id'],
            'entry_no' => $row->attributes['entry_no'],
            'entry_date' => $row->attributes['entry_date'],
            'description' => $row->attributes['description'] ?? null,
            'business_id' => $ctx->businessId,
            'branch_id' => $ctx->branchId,
            'status' => 'pending',
        ];

        if ($row->action === 'update') {
            $journalEntry = JournalEntry::findOrFail($row->matchedId);
            $journalEntry->update(array_merge($attributes, [
                'updatedby_id' => $ctx->userId,
                'date_updated' => now(),
                'date_posted' => null,
            ]));

            JournalEntryDetail::where('journal_entry_id', $journalEntry->journal_entry_id)->delete();
            $created = false;
        } else {
            $journalEntry = JournalEntry::create(array_merge($attributes, [
                'journal_entry_id' => generateUuid(),
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]));
            $created = true;
        }

        foreach ($row->children as $child) {
            if ($child->action === 'invalid') {
                continue;
            }

            JournalEntryDetail::create([
                'journal_entry_detail_id' => generateUuid(),
                'journal_entry_id' => $journalEntry->journal_entry_id,
                'account_id' => $child->attributes['account_id'],
                'debit' => (float) ($child->attributes['debit'] ?? 0),
                'credit' => (float) ($child->attributes['credit'] ?? 0),
                'description' => $child->attributes['description'] ?? null,
            ]);
        }

        // Authoritative check, not a repeat of applyDomainValidation()'s
        // pre-save row marking above - this asserts against what was
        // actually persisted, so it also catches any future bug in the
        // pre-validation math instead of only trusting it.
        JournalEntryService::assertBalanced($journalEntry->journal_entry_id);

        return ['model' => $journalEntry, 'created' => $created];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = JournalEntry::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('entry_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['journal', 'journalEntryDetails.account'];
    }
}
