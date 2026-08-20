<?php

namespace App\Services\ImportExport\Modules\Expense;

use App\Enums\Status;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by both the "Expense Detail" screen (ExpenseController, moduleKey
 * 'expense') and the "Admin Expense" screen (AdminExpenseController,
 * moduleKey 'admin-expense') - both are the same `expenses` table/Expense
 * model, only filtered/labelled differently by their controllers. Registered
 * twice in ImportExportModuleRegistry with different constructor args.
 *
 * Neither screen's import supports attaching a POS register session or an
 * Order Taker/user (there is no sane flat-Excel representation of "pick an
 * active session"), so imported rows always get
 * pos_register_session_id = null and user_id = null, and - mirroring both
 * controllers' store(), which unconditionally set $obj['source'] = 'admin'
 * regardless of what's submitted - source is always forced to 'admin'. This
 * makes every imported Expense/Admin Expense row behave identically
 * regardless of which of the two screens it was imported from; $adminOnly is
 * kept (and threaded through) only to parametrize moduleKey()/label() and
 * for any future divergence between the two screens' import behavior.
 */
class ExpenseImportExportDefinition extends AbstractImportExportDefinition
{
    public function __construct(
        protected string $moduleKeyValue = 'expense',
        protected string $labelValue = 'Expenses',
        protected bool $adminOnly = false
    ) {
    }

    public function moduleKey(): string
    {
        return $this->moduleKeyValue;
    }

    public function label(): string
    {
        return $this->labelValue;
    }

    public function modelClass(): string
    {
        return Expense::class;
    }

    public function primaryKey(): string
    {
        return 'expense_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Expense No',
                attribute: 'expense_no',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'expense_no',
                notes: 'Leave blank to create a new expense (a new Expense No is generated automatically, matching the Add New screen). To update an existing expense instead, enter its exact existing Expense No.',
            ),
            new ColumnDefinition(
                key: 'Date',
                attribute: 'expense_date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-01', '2026-08-05'],
                exportAccessor: 'expense_date',
            ),
            new ColumnDefinition(
                key: 'Expense Category',
                attribute: 'expense_category_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(ExpenseCategory::class, 'expense-category', 'name', scopeToBusiness: true),
                sampleValues: ['Rent', 'Utilities'],
                exportAccessor: fn ($m) => $m->category->name ?? '',
                notes: 'Copy the exact Name from the Expense Categories list.',
            ),
            new ColumnDefinition(
                key: 'Amount',
                attribute: 'amount',
                type: 'decimal',
                required: true,
                sampleValues: ['1500', '250.50'],
                exportAccessor: 'amount',
            ),
            new ColumnDefinition(
                key: 'Payment Account',
                attribute: 'payment_account_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Account::class, 'account', 'name', scopeToBusiness: true),
                sampleValues: ['Cash', 'Main Bank'],
                exportAccessor: fn ($m) => $m->paymentAccount->name ?? '',
                notes: 'Copy the exact Account Name from the Chart of Accounts.',
            ),
            new ColumnDefinition(
                key: 'Description',
                attribute: 'description',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'description',
            ),
            new ColumnDefinition(
                key: 'Branch',
                attribute: 'branch_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Branch::class, 'branch', 'name', scopeToBusiness: true),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->branch->name ?? '',
                notes: 'Leave blank to use your own branch. Copy the exact Branch Name from the Branches list.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['expense_no'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Expense::query()->where('is_deleted', 0);

        if ($this->adminOnly) {
            $query->whereNull('pos_register_session_id')->where('source', 'admin');
        }

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('expense_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'category', 'paymentAccount'];
    }

    /**
     * Mirrors the attribute-shaping half of ExpenseService::save() (expense_no
     * generation, the category -> expense_account_id snapshot, forced
     * admin/no-session defaults) while keeping the generic engine's "a blank
     * cell never overwrites an existing value on update" rule for every
     * column that IS user-editable via import - unlike ExpenseService::save()
     * (which re-shapes every field from a full form re-submission),
     * confirming an update here only ever touches the columns actually
     * present in the sheet, e.g. it never resets an existing Expense's
     * Payment Method, Reference No, or POS session/OT linkage.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $providedAttributes = array_filter($row->attributes, fn ($v) => $v !== null);

        $businessId = $ctx->businessId;
        $categoryId = $providedAttributes['expense_category_id'] ?? null;
        $category = $categoryId ? ExpenseCategory::find($categoryId) : null;
        $accountingSetting = AccountingSetting::where('business_id', $businessId)->first();
        $expenseAccountId = $category->account_id ?? ($accountingSetting->default_expense_account_id ?? null);

        if ($row->action === 'update') {
            $expense = Expense::findOrFail($row->matchedId);

            $data = $providedAttributes;
            unset($data['expense_no']); // Expense No is a match key only - never rewritten by an update.

            if ($categoryId) {
                $data['expense_account_id'] = $expenseAccountId;
            }

            $data['updatedby_id'] = $ctx->userId;
            $data['date_updated'] = now();

            $expense->update($data);
            $created = false;
        } else {
            $data = array_merge($providedAttributes, [
                'expense_id' => generateUuid(),
                'business_id' => $businessId,
                'branch_id' => $providedAttributes['branch_id'] ?? $ctx->branchId ?? null,
                'pos_register_session_id' => null,
                'user_id' => null,
                'expense_no' => generateExpenseNo($businessId),
                'expense_account_id' => $expenseAccountId,
                'payment_method' => 'cash',
                'source' => 'admin',
                'status' => Status::PENDING,
                'is_deleted' => 0,
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);

            $expense = Expense::create($data);
            $created = true;
        }

        return ['model' => $expense, 'created' => $created];
    }
}
