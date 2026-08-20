<?php

namespace App\Services\ImportExport\Modules\ExpenseCategory;

use App\Models\Account;
use App\Models\ExpenseCategory;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

class ExpenseCategoryImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'expense-category';
    }

    public function label(): string
    {
        return 'Expense Categories';
    }

    public function modelClass(): string
    {
        return ExpenseCategory::class;
    }

    public function primaryKey(): string
    {
        return 'expense_category_id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Rent', 'Utilities'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Account',
                attribute: 'account_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Account::class, 'account', 'name', scopeToBusiness: true),
                sampleValues: ['Rent Expense', ''],
                exportAccessor: fn ($m) => $m->account->name ?? '',
                notes: 'Copy the exact Account Name from the Chart of Accounts. Leave blank to use the business default expense account ("Use Business Default Expense Account").',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: false,
                enumValues: ['active', 'inactive'],
                sampleValues: ['active', 'active'],
                exportAccessor: 'status',
                notes: 'Defaults to "active" if left blank.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['name'];
    }

    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        // Mirrors the Add New modal's own default (js: $('#use_default_account').prop('checked', true)).
        return ['status' => 'active', 'use_default_account' => true];
    }

    /**
     * Mirrors ExpenseCategoryController::store()'s "use_default_account xor
     * account_id" rule: whenever an explicit Account is imported, flip the
     * "Use Business Default Expense Account" flag off. When the Account
     * column is left blank we simply don't touch use_default_account here -
     * additionalCreateAttributes() already defaults it to true on create,
     * and leaving it untouched on update preserves whatever the record
     * already had (consistent with every other optional column in this
     * engine: a blank cell never overwrites an existing value).
     */
    protected function applyDomainValidation(ResolvedRow $row, ImportContext $ctx): void
    {
        if ($row->action === 'invalid') {
            return;
        }

        if (!empty($row->attributes['account_id'])) {
            $row->attributes['use_default_account'] = false;
        }
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = ExpenseCategory::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'account'];
    }
}
