<?php

namespace App\Services\ImportExport\Modules\CustomerPayment;

use App\Enums\PaymentMethod;
use App\Models\Account;
use App\Models\CustomerPayment;
use App\Models\User;
use App\Services\Concrete\Admin\CustomerPaymentService;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

/**
 * CustomerPaymentService::save() is structurally the twin of
 * SupplierPaymentService::save() (see SupplierPaymentImportExportDefinition,
 * which this class mirrors): it derives payment_account_id from Accounting
 * Settings, resolves the customer's receivable COA, computes net_amount
 * (throwing if amount/tax/discount would go negative or exceed a targeted
 * order/service-sale's remaining due), and auto-generates payment_no on
 * create. save() below delegates to that same Service rather than
 * reimplementing it.
 *
 * Crucially, a CustomerPayment is only ever created/updated at status
 * "Pending" here - identical to what a manual Add New does. Posting the
 * actual CRV/BRV Journal Entry only happens later via the separate
 * "Change Status -> Posted" action (CustomerPaymentService::applyPosting()),
 * so importing rows never posts a Journal Entry as a side effect.
 *
 * order_id / service_sale_id are deliberately not columns here - they stay
 * null on every imported row (imported payments are always unallocated/
 * on-account), exactly like Supplier Payment's purchase_id. Order No /
 * Service Sale No are exposed as EXPORT-ONLY columns for reference.
 *
 * The "Customer" relation resolves against User by email with
 * scopeToBusiness: false - a customer's users.business_id is only the first
 * business that ever created that person (UserService::save()'s global
 * account-reuse rule), not necessarily the business being imported into,
 * even though they may legitimately hold a CustomerProfile here. Matching by
 * the globally-unique email is correct; CustomerPaymentService::save()
 * already throws a clear "Selected customer does not have a profile for
 * this business" error if the matched user has no profile for this
 * business, which the per-row transaction in ImportWriterService catches.
 */
class CustomerPaymentImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'customer-payment';
    }

    public function label(): string
    {
        return 'Customer Payments';
    }

    public function modelClass(): string
    {
        return CustomerPayment::class;
    }

    public function primaryKey(): string
    {
        return 'customer_payment_id';
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
                key: 'Customer',
                attribute: 'user_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(User::class, 'customer', 'email', scopeToBusiness: false, relatedLabel: 'Customer'),
                sampleValues: ['jane.doe@example.com', 'john.smith@example.com'],
                exportAccessor: fn ($m) => $m->user->email ?? '',
                notes: 'Copy the exact Email from the Customers list.',
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
                enumValues: [PaymentMethod::CASH, PaymentMethod::BANK_TRANSFER, PaymentMethod::CHEQUE, PaymentMethod::ONLINE, PaymentMethod::CARD],
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
                key: 'Reference No',
                attribute: 'reference_no',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'reference_no',
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
                key: 'Order No',
                attribute: 'order_no_display_only',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->order->daily_order_id ?? '',
                notes: 'Read-only. Linking a payment to a specific Order cannot be done via import; imported payments are always unallocated.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['payment_no'];
    }

    /**
     * Delegates the actual save to CustomerPaymentService::save() - see
     * class docblock for why a plain Model::create()/update() would be
     * wrong here.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $providedAttributes = array_filter($row->attributes, fn ($v) => $v !== null);
        unset($providedAttributes['order_no_display_only']);

        $data = array_merge($providedAttributes, [
            'business_id' => $ctx->businessId,
            'branch_id' => $providedAttributes['branch_id'] ?? $ctx->branchId ?? null,
            'order_id' => null,
            'service_sale_id' => null,
        ]);

        if ($row->action === 'update') {
            $data['customer_payment_id'] = $row->matchedId;
        }

        $payment = app(CustomerPaymentService::class)->save($data);

        return ['model' => $payment, 'created' => $row->action !== 'update'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = CustomerPayment::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('payment_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch', 'user', 'paymentAccount', 'order'];
    }
}
