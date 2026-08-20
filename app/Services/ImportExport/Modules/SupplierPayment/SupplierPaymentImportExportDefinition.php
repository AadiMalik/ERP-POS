<?php

namespace App\Services\ImportExport\Modules\SupplierPayment;

use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Concrete\Admin\SupplierPaymentService;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

/**
 * SupplierPaymentService::save() does meaningful accounting-adjacent work
 * beyond a plain insert - it derives payment_account_id from Accounting
 * Settings (cash/bank default, unless manual_payment_account_selection is
 * enabled, in which case the submitted payment_account_id is used), snapshots
 * the Supplier's own account_id onto supplier_account_id, computes net_amount
 * from amount/tax/discount (throwing if it would go negative), and
 * auto-generates payment_no on create. save() below delegates to that same
 * Service rather than reimplementing or bypassing it, exactly as the
 * ExpenseImportExportDefinition precedent recommends for a module whose
 * Service does more than persist raw attributes.
 *
 * Note this only ever creates/updates the supplier_payments row itself, at
 * status "pending" - identical to what a manual Add New does. Posting the
 * actual CPV/BPV Journal Entry only happens later via the separate
 * "Change Status" -> Posted action (SupplierPaymentService::applyPosting()),
 * so importing rows never posts a Journal Entry as a side effect.
 *
 * purchase_id (the optional Purchase linkage) is deliberately not a column
 * here - it stays null on every imported row (see class-level task notes);
 * Purchase No is exposed as an EXPORT-ONLY column for reference.
 */
class SupplierPaymentImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'supplier-payment';
    }

    public function label(): string
    {
        return 'Supplier Payments';
    }

    public function modelClass(): string
    {
        return SupplierPayment::class;
    }

    public function primaryKey(): string
    {
        return 'supplier_payment_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Payment No',
                attribute: 'payment_no',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'payment_no',
                notes: 'Leave blank to create a new payment (a new Payment No is generated automatically, matching the Add New screen). To update an existing payment instead, enter its exact existing Payment No. Only Pending payments can be updated.',
            ),
            new ColumnDefinition(
                key: 'Date',
                attribute: 'payment_date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-01', '2026-08-05'],
                exportAccessor: 'payment_date',
            ),
            new ColumnDefinition(
                key: 'Supplier',
                attribute: 'supplier_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Supplier::class, 'supplier', 'name', scopeToBusiness: true),
                sampleValues: ['ABC Traders', 'Global Supplies Co.'],
                exportAccessor: fn ($m) => $m->supplier->name ?? '',
                notes: 'Copy the exact Name from the Suppliers list.',
            ),
            new ColumnDefinition(
                key: 'Amount',
                attribute: 'amount',
                type: 'decimal',
                required: true,
                sampleValues: ['5000', '1250.50'],
                exportAccessor: 'amount',
            ),
            new ColumnDefinition(
                key: 'Payment Method',
                attribute: 'payment_method',
                type: 'enum',
                required: true,
                enumValues: ['cash', 'bank_transfer', 'cheque', 'online'],
                sampleValues: ['cash', 'bank_transfer'],
                exportAccessor: 'payment_method',
            ),
            new ColumnDefinition(
                key: 'Payment Account',
                attribute: 'payment_account_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Account::class, 'account', 'name', scopeToBusiness: true),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->paymentAccount->name ?? '',
                notes: 'Only used when "Manual Payment Account Selection" is enabled in Accounting Settings; otherwise the Cash/Bank default account is used automatically based on Payment Method. Copy the exact Account Name from the Chart of Accounts.',
            ),
            new ColumnDefinition(
                key: 'Description',
                attribute: 'remarks',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'remarks',
            ),
            new ColumnDefinition(
                key: 'Purchase No',
                attribute: 'purchase_no_display_only',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->purchase->purchase_no ?? '',
                notes: 'Read-only. Linking a payment to a specific Purchase cannot be done via import; imported payments are always unallocated.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['payment_no'];
    }

    /**
     * Delegates the actual save to SupplierPaymentService::save() - see
     * class docblock for why a plain Model::create()/update() would be
     * wrong here.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $providedAttributes = array_filter($row->attributes, fn ($v) => $v !== null);
        unset($providedAttributes['purchase_no_display_only']);

        $data = array_merge($providedAttributes, [
            'business_id' => $ctx->businessId,
            'branch_id' => $providedAttributes['branch_id'] ?? $ctx->branchId ?? null,
            'purchase_id' => null,
        ]);

        if ($row->action === 'update') {
            $data['supplier_payment_id'] = $row->matchedId;
        }

        $payment = app(SupplierPaymentService::class)->save($data);

        return ['model' => $payment, 'created' => $row->action !== 'update'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = SupplierPayment::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('payment_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'supplier', 'paymentAccount', 'purchase'];
    }
}
