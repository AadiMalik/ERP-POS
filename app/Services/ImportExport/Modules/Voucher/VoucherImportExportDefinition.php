<?php

namespace App\Services\ImportExport\Modules\Voucher;

use App\Models\Branch;
use App\Models\Category;
use App\Models\OrderType;
use App\Models\Product;
use App\Models\User;
use App\Models\Voucher;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class VoucherImportExportDefinition extends AbstractImportExportDefinition
{
    // Maps each pivot column's model attribute (holding the array of resolved
    // IDs after RelationResolverService::resolve()) to the belongsToMany
    // relation method on Voucher and the raw Excel header used to detect
    // whether the cell was left blank (see save() below).
    protected array $pivotAttributeMap = [
        'product_ids' => ['relation' => 'products', 'rawKey' => 'Products'],
        'category_ids' => ['relation' => 'categories', 'rawKey' => 'Categories'],
        'user_ids' => ['relation' => 'users', 'rawKey' => 'Customers'],
        'order_type_ids' => ['relation' => 'orderTypes', 'rawKey' => 'Order Types'],
        'branch_ids' => ['relation' => 'branches', 'rawKey' => 'Branches'],
    ];

    public function moduleKey(): string
    {
        return 'voucher';
    }

    public function label(): string
    {
        return 'Vouchers';
    }

    public function modelClass(): string
    {
        return Voucher::class;
    }

    public function primaryKey(): string
    {
        return 'voucher_id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Code',
                attribute: 'code',
                type: 'string',
                required: true,
                sampleValues: ['WELCOME10', 'SUMMER25'],
                exportAccessor: 'code',
            ),
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Welcome Discount', 'Summer Sale'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Type',
                attribute: 'type',
                type: 'enum',
                required: true,
                enumValues: ['percent', 'fixed'],
                sampleValues: ['percent', 'fixed'],
                exportAccessor: 'type',
                notes: 'Whether "Value" below is a percentage discount or a fixed amount discount.',
            ),
            new ColumnDefinition(
                key: 'Value',
                attribute: 'value',
                type: 'decimal',
                required: true,
                sampleValues: ['10', '25.000'],
                exportAccessor: 'value',
                notes: 'The discount amount - a percent (e.g. 10 for 10%) or a fixed currency amount, depending on "Type".',
            ),
            new ColumnDefinition(
                key: 'Valid From',
                attribute: 'valid_from',
                type: 'date',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'valid_from',
                notes: 'Leave blank for no start date restriction.',
            ),
            new ColumnDefinition(
                key: 'Valid To',
                attribute: 'valid_to',
                type: 'date',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'valid_to',
                notes: 'Leave blank for no expiry date.',
            ),
            new ColumnDefinition(
                key: 'Total Usage Limit',
                attribute: 'usage_limit_total',
                type: 'integer',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'usage_limit_total',
                notes: 'Leave blank for unlimited total redemptions.',
            ),
            new ColumnDefinition(
                key: 'Per Customer Usage Limit',
                attribute: 'usage_limit_per_customer',
                type: 'integer',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'usage_limit_per_customer',
                notes: 'Leave blank for unlimited redemptions per customer.',
            ),
            new ColumnDefinition(
                key: 'Min Order Amount',
                attribute: 'min_order_amount',
                type: 'decimal',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'min_order_amount',
                notes: 'Leave blank for no minimum order amount.',
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
            new ColumnDefinition(
                key: 'Products',
                attribute: 'product_ids',
                type: 'relation',
                required: false,
                relation: new RelationSpec(
                    Product::class,
                    'product',
                    'name',
                    scopeToBusiness: true,
                    allowMultiple: true,
                    pivotTable: 'voucher_products',
                    pivotForeignKey: 'voucher_id',
                    pivotRelatedKey: 'product_id',
                ),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->products->pluck('name')->implode(', '),
                notes: 'Comma-separate multiple product names; copy exact names from the Products list. Leave blank to apply this voucher to all products; leave unchanged on update by leaving this cell blank.',
            ),
            new ColumnDefinition(
                key: 'Categories',
                attribute: 'category_ids',
                type: 'relation',
                required: false,
                relation: new RelationSpec(
                    Category::class,
                    'category',
                    'name',
                    scopeToBusiness: true,
                    allowMultiple: true,
                    pivotTable: 'voucher_categories',
                    pivotForeignKey: 'voucher_id',
                    pivotRelatedKey: 'category_id',
                ),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->categories->pluck('name')->implode(', '),
                notes: 'Comma-separate multiple category names; copy exact names from the Categories list. Leave blank to apply this voucher to all categories; leave unchanged on update by leaving this cell blank.',
            ),
            new ColumnDefinition(
                key: 'Customers',
                attribute: 'user_ids',
                type: 'relation',
                required: false,
                relation: new RelationSpec(
                    User::class,
                    'user',
                    'email',
                    scopeToBusiness: true,
                    allowMultiple: true,
                    pivotTable: 'voucher_customers',
                    pivotForeignKey: 'voucher_id',
                    pivotRelatedKey: 'user_id',
                    relatedLabel: 'Customer',
                ),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->users->pluck('email')->implode(', '),
                notes: 'Comma-separate multiple customer emails; copy exact email addresses from the Customers list. Leave blank to apply this voucher to all customers; leave unchanged on update by leaving this cell blank.',
            ),
            new ColumnDefinition(
                key: 'Order Types',
                attribute: 'order_type_ids',
                type: 'relation',
                required: false,
                relation: new RelationSpec(
                    OrderType::class,
                    'order-type',
                    'name',
                    scopeToBusiness: true,
                    allowMultiple: true,
                    pivotTable: 'voucher_order_types',
                    pivotForeignKey: 'voucher_id',
                    pivotRelatedKey: 'order_type_id',
                ),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->orderTypes->pluck('name')->implode(', '),
                notes: 'Comma-separate multiple order type names; copy exact names from the Order Types list. Leave blank to apply this voucher to all order types; leave unchanged on update by leaving this cell blank.',
            ),
            new ColumnDefinition(
                key: 'Branches',
                attribute: 'branch_ids',
                type: 'relation',
                required: false,
                relation: new RelationSpec(
                    Branch::class,
                    'branch',
                    'name',
                    scopeToBusiness: true,
                    allowMultiple: true,
                    pivotTable: 'voucher_branches',
                    pivotForeignKey: 'voucher_id',
                    pivotRelatedKey: 'branch_id',
                ),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->branches->pluck('name')->implode(', '),
                notes: 'Comma-separate multiple branch names; copy exact names from the Branches list. Leave blank to apply this voucher to all branches; leave unchanged on update by leaving this cell blank.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['code'];
    }

    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        return ['status' => 'active', 'used_count' => 0];
    }

    /**
     * The generic AbstractImportExportDefinition::save() only knows how to
     * persist a single hasMany child table via childDefinition(), which
     * doesn't fit Voucher's shape (a header row plus 5 independent
     * belongsToMany scope-pivots). Overridden fully instead, mirroring
     * VoucherService::save()/syncScopePivots()'s delete-then-reinsert
     * behavior: a pivot column left blank in the Excel row means "don't
     * touch this pivot" (checked via the raw cell, since a blank cell also
     * resolves to an empty ids array - same as "clear this pivot" would),
     * not "clear it".
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $headerAttributes = Arr::except($row->attributes, array_keys($this->pivotAttributeMap));
        // Blank/omitted optional cells resolve to null; strip them so they
        // never overwrite an existing value on update or a default on create.
        $providedAttributes = array_filter($headerAttributes, fn ($v) => $v !== null);
        $attributes = array_merge($providedAttributes, $this->contextAttributes($ctx));

        if ($row->action === 'update') {
            $voucher = Voucher::findOrFail($row->matchedId);
            $voucher->update(array_merge($attributes, [
                'updatedby_id' => $ctx->userId,
                'date_updated' => now(),
            ]));
            $created = false;
        } else {
            $voucher = Voucher::create(array_merge(
                $this->additionalCreateAttributes($ctx),
                $attributes,
                [
                    'voucher_id' => generateUuid(),
                    'createdby_id' => $ctx->userId,
                    'date_created' => now(),
                ]
            ));
            $created = true;
        }

        foreach ($this->pivotAttributeMap as $attribute => $meta) {
            $rawValue = $row->raw[$meta['rawKey']] ?? null;
            if ($rawValue === null || trim((string) $rawValue) === '') {
                // Blank cell = leave this pivot untouched (don't wipe it via sync([])).
                continue;
            }

            $ids = $row->attributes[$attribute] ?? [];
            $voucher->{$meta['relation']}()->sync($ids);
        }

        return ['model' => $voucher, 'created' => $created];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Voucher::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['products', 'categories', 'users', 'orderTypes', 'branches'];
    }
}
