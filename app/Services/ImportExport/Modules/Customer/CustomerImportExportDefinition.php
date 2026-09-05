<?php

namespace App\Services\ImportExport\Modules\Customer;

use App\Enums\RoleNames;
use App\Models\Branch;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\User;
use App\Services\Concrete\Admin\UserService;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Exception;
use Illuminate\Database\Eloquent\Builder;

/**
 * A Customer is a `users` row with the RoleNames::USER role plus a
 * business-scoped CustomerProfile (see CustomerController/UserService/
 * CustomerService docblocks). save() below delegates to the exact same
 * UserService::save() write path CustomerController::store() uses - which in
 * turn calls CustomerService::upsertProfile() - rather than touching User or
 * CustomerProfile directly, so every side effect (global-account reuse by
 * email, opening-balance journal posting on first-ever profile for this
 * business, default receivable COA assignment) stays identical to a manual
 * Add New Customer.
 *
 * modelClass()/primaryKey() point at User (not CustomerProfile) because the
 * natural match key - email - lives on `users`; matchedId is therefore a
 * User id, passed back into UserService::save() as $obj['id'] on update
 * (mirroring UserImportExportDefinition's own int-PK override).
 */
class CustomerImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'customer';
    }

    public function label(): string
    {
        return 'Customers';
    }

    public function modelClass(): string
    {
        return User::class;
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Jane Doe', 'John Smith'],
                exportAccessor: fn ($m) => $m->user->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Email',
                attribute: 'email',
                type: 'string',
                required: true,
                sampleValues: ['jane.doe@example.com', 'john.smith@example.com'],
                exportAccessor: fn ($m) => $m->user->email ?? '',
                notes: 'Must be unique across the whole system (not just this business). A person who is already a customer of another business is reused by email rather than duplicated - copy the exact Email from the Customers list to update their profile for this business instead of creating a new person.',
            ),
            new ColumnDefinition(
                key: 'Phone',
                attribute: 'phone',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->user->phone ?? '',
            ),
            new ColumnDefinition(
                key: 'Branch',
                attribute: 'branch_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Branch::class, 'branch', 'name', scopeToBusiness: true),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->branch->name ?? '',
                notes: 'Leave blank for a business-level (non branch-specific) customer. Copy the exact Branch Name from the Branches list.',
            ),
            new ColumnDefinition(
                key: 'Code',
                attribute: 'code',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'code',
                notes: 'Leave blank to auto-generate a new Customer Code (matching the Add New screen).',
            ),
            new ColumnDefinition(
                key: 'Company Name',
                attribute: 'company_name',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'company_name',
            ),
            new ColumnDefinition(
                key: 'Contact Person',
                attribute: 'contact_person',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'contact_person',
            ),
            new ColumnDefinition(
                key: 'Address',
                attribute: 'address',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'address',
            ),
            new ColumnDefinition(
                key: 'City',
                attribute: 'city',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'city',
            ),
            new ColumnDefinition(
                key: 'State',
                attribute: 'state',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'state',
            ),
            new ColumnDefinition(
                key: 'Country',
                attribute: 'country',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'country',
            ),
            new ColumnDefinition(
                key: 'Shipping Address',
                attribute: 'shipping_address',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'shipping_address',
            ),
            new ColumnDefinition(
                key: 'Shipping City',
                attribute: 'shipping_city',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'shipping_city',
            ),
            new ColumnDefinition(
                key: 'Shipping State',
                attribute: 'shipping_state',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'shipping_state',
            ),
            new ColumnDefinition(
                key: 'Shipping Country',
                attribute: 'shipping_country',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'shipping_country',
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
                type: 'integer',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'credit_days',
            ),
            new ColumnDefinition(
                key: 'Opening Balance',
                attribute: 'opening_balance',
                type: 'decimal',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'opening_balance',
                notes: 'Only applied the first time a profile is created for this business (matching the Add New screen) - ignored on update. Requires Opening Balance Type to also be set.',
            ),
            new ColumnDefinition(
                key: 'Opening Balance Type',
                attribute: 'opening_balance_type',
                type: 'enum',
                required: fn ($raw) => !empty($raw['Opening Balance'] ?? null),
                enumValues: ['Dr', 'Cr'],
                sampleValues: ['', ''],
                exportAccessor: 'opening_balance_type',
                notes: 'Required when Opening Balance is set. "Dr" = customer owes the business, "Cr" = business owes the customer.',
            ),
            new ColumnDefinition(
                key: 'Payment Terms',
                attribute: 'payment_terms',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'payment_terms',
            ),
            new ColumnDefinition(
                key: 'Notes',
                attribute: 'notes',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'notes',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['email'];
    }

    public function uniqueKeyIsGlobal(): bool
    {
        // Mirrors the real DB unique constraint on users.email, which is not
        // scoped by business_id - see UserImportExportDefinition.
        return true;
    }

    /**
     * Delegates to UserService::save() exactly as CustomerController::store()
     * does, so role assignment, global-account reuse by email, and the
     * CustomerService::upsertProfile() side effects (default receivable COA,
     * opening balance posting) all run identically to a manual Add New.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $roleId = Role::where('name', RoleNames::USER)->whereNull('business_id')->value('id');

        if (!$roleId) {
            throw new Exception('Customer role is not configured.');
        }

        $providedAttributes = array_filter($row->attributes, fn ($v) => $v !== null);

        $obj = array_merge($providedAttributes, [
            'role_id' => $roleId,
            'status' => 'active',
            'business_id' => $ctx->businessId,
        ]);

        if ($row->action === 'update') {
            $obj['id'] = $row->matchedId;
        }

        $saved = app(UserService::class)->save($obj);

        if (!$saved) {
            throw new Exception('Failed to save customer.');
        }

        return ['model' => $saved, 'created' => $row->action !== 'update'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        // Same base query as CustomerService::getData(): the business-scoped
        // CustomerProfile, excluding the auto-created walk-in customer.
        $query = CustomerProfile::query()
            ->where('is_deleted', 0)
            ->where('is_walkin', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('date_created', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['user', 'business', 'branch', 'account'];
    }
}
