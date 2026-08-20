<?php

namespace App\Services\ImportExport\Modules\Supplier;

use App\Models\AccountingSetting;
use App\Models\Branch;
use App\Models\Supplier;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

/**
 * Two deliberate deviations from a "plain insert" Definition, both mirroring
 * SupplierController::store()/SupplierService::save() exactly:
 *
 * - Code is nullable at the DB level, but both the create screen and
 *   SupplierService::save() always auto-generate it via generateSupplierCode()
 *   when left blank, on every create path with no exception. uniqueKeyColumns()
 *   below therefore safely uses ['code'] - after save() runs once, every
 *   Supplier always has a non-null code, so a re-imported file (code now
 *   filled in from a previous export) correctly matches back to the same row.
 *   Code is treated as a match key only: an update never rewrites it.
 *
 * - account_id is NOT a Definition column at all. The Supplier create form
 *   has no Account field, and SupplierService::save() unconditionally
 *   overwrites account_id from session('accounting_setting.default_supplier_account_id')
 *   on both create AND update - it is never actually user-settable anywhere
 *   in the app. save() below reproduces that by reading the business's
 *   AccountingSetting row directly (by ctx->businessId, not session - the
 *   session may belong to a different business than the one being imported
 *   into, e.g. a Super Admin import; same reasoning ExpenseImportExportDefinition
 *   uses for its own AccountingSetting lookup). An "Account" column is still
 *   exposed for EXPORT ONLY (its attribute is a synthetic key save() never
 *   reads back), purely so an exported file shows which account a supplier
 *   is currently posted against.
 */
class SupplierImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'supplier';
    }

    public function label(): string
    {
        return 'Suppliers';
    }

    public function modelClass(): string
    {
        return Supplier::class;
    }

    public function primaryKey(): string
    {
        return 'supplier_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Code',
                attribute: 'code',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'code',
                notes: 'Leave blank to auto-generate a new Supplier Code (matching the Add New screen). To update an existing supplier instead, enter its exact existing Code.',
            ),
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['ABC Traders', 'Global Supplies Co.'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Company Name',
                attribute: 'company_name',
                type: 'string',
                required: true,
                sampleValues: ['ABC Traders Pvt Ltd', 'Global Supplies Company'],
                exportAccessor: 'company_name',
                notes: 'Required, matching the Add New Supplier screen.',
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
            new ColumnDefinition(
                key: 'Credit Limit',
                attribute: 'credit_limit',
                type: 'decimal',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'credit_limit',
            ),
            new ColumnDefinition(
                key: 'Credit Days',
                attribute: 'credit_days',
                type: 'decimal',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'credit_days',
                notes: 'Stored as a decimal column in the database, matching the Add New Supplier screen.',
            ),
            new ColumnDefinition(
                key: 'Account',
                attribute: 'account_display_only',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->account->name ?? '',
                notes: 'Read-only. The supplier account is always auto-assigned from Accounting Settings (Default Supplier Account) and cannot be set via import.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['code'];
    }

    /**
     * Mirrors SupplierService::save(): auto-generates Code on create when
     * blank, and always re-derives account_id from Accounting Settings
     * (never from user input) on both create and update.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $providedAttributes = array_filter($row->attributes, fn ($v) => $v !== null);
        unset($providedAttributes['account_display_only']);

        $businessId = $ctx->businessId;
        $accountingSetting = AccountingSetting::where('business_id', $businessId)->first();
        $accountId = $accountingSetting->default_supplier_account_id ?? null;

        if ($row->action === 'update') {
            $supplier = Supplier::findOrFail($row->matchedId);

            $data = $providedAttributes;
            unset($data['code']); // Code is a match key only - never rewritten by an update.
            $data['account_id'] = $accountId;
            $data['updatedby_id'] = $ctx->userId;
            $data['date_updated'] = now();

            $supplier->update($data);
            $created = false;
        } else {
            $data = array_merge($providedAttributes, [
                'supplier_id' => generateUuid(),
                'business_id' => $businessId,
                'branch_id' => $providedAttributes['branch_id'] ?? $ctx->branchId ?? null,
                'code' => $providedAttributes['code'] ?? generateSupplierCode($businessId),
                'account_id' => $accountId,
                'status' => 'active',
                'is_deleted' => 0,
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);

            $supplier = Supplier::create($data);
            $created = true;
        }

        return ['model' => $supplier, 'created' => $created];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Supplier::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'account'];
    }
}
