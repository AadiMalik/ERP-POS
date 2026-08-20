<?php

namespace App\Services\ImportExport\Modules\RecurringTransaction;

use App\Models\RecurringTransaction;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

/**
 * Deliberately header-field UPDATES ONLY - a Recurring Transaction's real
 * template_data JSON payload (expense/journal entry template) cannot be
 * expressed in a flat Excel row, so import can never create a new schedule,
 * only adjust the schedule fields of one that already exists (matched by
 * Name). See applyDomainValidation() below, which hard-blocks the create
 * path the generic engine would otherwise take for an unmatched name.
 */
class RecurringTransactionImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'recurring-transaction';
    }

    public function label(): string
    {
        return 'Recurring Transactions';
    }

    public function modelClass(): string
    {
        return RecurringTransaction::class;
    }

    public function primaryKey(): string
    {
        return 'recurring_transaction_id';
    }

    public function isBranchScoped(): bool
    {
        return false;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Monthly Office Rent', 'Monthly Office Rent'],
                exportAccessor: 'name',
                notes: 'Must exactly match the Name of an existing Recurring Transaction - import can only update, never create.',
            ),
            new ColumnDefinition(
                key: 'Frequency',
                attribute: 'frequency',
                type: 'enum',
                required: false,
                enumValues: ['daily', 'weekly', 'monthly', 'yearly'],
                sampleValues: ['', ''],
                exportAccessor: 'frequency',
            ),
            new ColumnDefinition(
                key: 'Start Date',
                attribute: 'start_date',
                type: 'date',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'start_date',
            ),
            new ColumnDefinition(
                key: 'End Date',
                attribute: 'end_date',
                type: 'date',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'end_date',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: false,
                enumValues: ['active', 'paused', 'completed', 'cancelled'],
                sampleValues: ['', ''],
                exportAccessor: 'status',
                notes: 'Leave blank to keep the current status. Setting this directly does not recompute Next Run Date the way the Pause/Resume/Cancel actions do.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['name'];
    }

    public function canImport(): bool
    {
        return true;
    }

    /**
     * The generic engine would otherwise treat an unmatched Name as a new
     * record to create - block that here since import is update-only for
     * this module (see class docblock).
     */
    protected function applyDomainValidation(ResolvedRow $row, ImportContext $ctx): void
    {
        if ($row->action === 'create') {
            $row->action = 'invalid';
            $row->errors[] = [
                'column' => 'Name',
                'value' => $row->attributes['name'] ?? $row->groupKey,
                'reason' => "No existing Recurring Transaction named \"{$row->attributes['name']}\" was found. Import can only update recurring transactions created via the Recurring Transactions screen, not create new ones.",
            ];
        }
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = RecurringTransaction::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch'];
    }
}
