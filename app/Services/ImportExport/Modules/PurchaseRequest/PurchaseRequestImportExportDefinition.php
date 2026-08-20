<?php

namespace App\Services\ImportExport\Modules\PurchaseRequest;

use App\Enums\Status;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ChildTableDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

class PurchaseRequestImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'purchase-request';
    }

    public function label(): string
    {
        return 'Purchase Requests';
    }

    public function modelClass(): string
    {
        return PurchaseRequest::class;
    }

    public function primaryKey(): string
    {
        return 'purchase_request_id';
    }

    public function isBranchScoped(): bool
    {
        return false;
    }

    public function groupKeyColumn(): ?string
    {
        return 'Purchase Request No';
    }

    public function childRelationName(): ?string
    {
        return 'purchaseRequestDetails';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Purchase Request No',
                attribute: 'purchase_request_no',
                type: 'string',
                required: true,
                sampleValues: ['PR-0001', 'PR-0002'],
                exportAccessor: 'purchase_request_no',
                notes: 'Repeat this exact value on every line item belonging to the same purchase request.',
            ),
            new ColumnDefinition(
                key: 'Date',
                attribute: 'purchase_request_date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-20', '2026-08-21'],
                exportAccessor: 'purchase_request_date',
            ),
            new ColumnDefinition(
                key: 'Supplier',
                attribute: 'supplier_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Supplier::class, 'supplier', 'name', scopeToBusiness: true),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->supplier->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Warehouse',
                attribute: 'warehouse_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Warehouse::class, 'warehouse', 'name', scopeToBusiness: true),
                sampleValues: ['Main Store', 'Main Store'],
                exportAccessor: fn ($m) => $m->warehouse->name ?? '',
            ),
        ];
    }

    public function childDefinition(): ?ChildTableDefinition
    {
        return new ChildTableDefinition(
            modelClass: PurchaseRequestDetail::class,
            primaryKey: 'purchase_request_detail_id',
            foreignKeyAttribute: 'purchase_request_id',
            columns: [
                new ColumnDefinition(
                    key: 'Product',
                    attribute: 'product_id',
                    type: 'relation',
                    required: true,
                    relation: new RelationSpec(Product::class, 'product', 'name', scopeToBusiness: true),
                    sampleValues: ['Coca Cola 500ml', 'Coca Cola 500ml'],
                    notes: 'Copy the exact Product Name from the Products list.',
                ),
                new ColumnDefinition(
                    key: 'Variation SKU',
                    attribute: 'product_variation_id',
                    type: 'relation',
                    required: true,
                    relation: new RelationSpec(ProductVariation::class, 'product', 'sku', scopeToBusiness: true, relatedLabel: 'Product Variation'),
                    sampleValues: ['CC500-001', 'CC500-001'],
                    notes: 'Copy the exact SKU from the product\'s Variations list on the Products screen.',
                ),
                new ColumnDefinition(
                    key: 'Unit',
                    attribute: 'unit_id',
                    type: 'relation',
                    required: true,
                    relation: new RelationSpec(Unit::class, 'unit', 'name', scopeToBusiness: false),
                    sampleValues: ['Piece', 'Piece'],
                ),
                new ColumnDefinition(
                    key: 'Quantity',
                    attribute: 'requested_quantity',
                    type: 'decimal',
                    required: true,
                    sampleValues: ['10', '5'],
                ),
            ],
            minChildren: 1,
        );
    }

    public function uniqueKeyColumns(): array
    {
        return ['purchase_request_no'];
    }

    /**
     * Mirrors the store() validation's products.*.requested_quantity
     * min:0.0001 rule.
     */
    protected function applyDomainValidation(ResolvedRow $row, ImportContext $ctx): void
    {
        foreach ($row->children as $child) {
            if ($child->action === 'invalid') {
                continue;
            }

            $quantity = (float) ($child->attributes['requested_quantity'] ?? 0);

            if ($quantity < 0.0001) {
                $child->action = 'invalid';
                $child->errors[] = ['column' => 'Quantity', 'value' => $quantity, 'reason' => 'Quantity must be greater than 0.'];
            }
        }
    }

    /**
     * Mirrors PurchaseRequestService::save(). Purchase Expected Date isn't an
     * import column (out of the brief's scope), so it's set equal to the
     * request date on create, which always satisfies the manual form's
     * after_or_equal:purchase_request_date rule. Only pending purchase
     * requests can be updated via import - once a request has moved past
     * pending (approved/quotation sent/etc.) its line items are tied to
     * quotations already sent to suppliers, so re-importing must not
     * silently rewrite them.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $warehouse = Warehouse::findOrFail($row->attributes['warehouse_id']);
        $businessId = $warehouse->business_id;
        $branchId = $warehouse->branch_id;

        if ($row->action === 'update') {
            $purchaseRequest = PurchaseRequest::findOrFail($row->matchedId);

            if ($purchaseRequest->status !== Status::PENDING) {
                throw new \Exception('Only pending purchase requests can be updated via import.');
            }

            $purchaseRequest->update([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouse->warehouse_id,
                'supplier_id' => $row->attributes['supplier_id'] ?? null,
                'purchase_request_date' => $row->attributes['purchase_request_date'],
                'status' => Status::PENDING,
                'updatedby_id' => $ctx->userId,
                'date_updated' => now(),
            ]);

            PurchaseRequestDetail::where('purchase_request_id', $purchaseRequest->purchase_request_id)->delete();
            $created = false;
        } else {
            $purchaseRequest = PurchaseRequest::create([
                'purchase_request_id' => generateUuid(),
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouse->warehouse_id,
                'supplier_id' => $row->attributes['supplier_id'] ?? null,
                'purchase_request_no' => $row->attributes['purchase_request_no'],
                'purchase_request_date' => $row->attributes['purchase_request_date'],
                'purchase_expected_date' => $row->attributes['purchase_request_date'],
                'status' => Status::PENDING,
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);
            $created = true;
        }

        foreach ($row->children as $child) {
            if ($child->action === 'invalid') {
                continue;
            }

            PurchaseRequestDetail::create([
                'purchase_request_detail_id' => generateUuid(),
                'purchase_request_id' => $purchaseRequest->purchase_request_id,
                'product_id' => $child->attributes['product_id'],
                'product_variation_id' => $child->attributes['product_variation_id'],
                'unit_id' => $child->attributes['unit_id'],
                'requested_quantity' => $child->attributes['requested_quantity'],
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);
        }

        return ['model' => $purchaseRequest, 'created' => $created];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = PurchaseRequest::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('purchase_request_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['supplier', 'warehouse', 'purchaseRequestDetails.product', 'purchaseRequestDetails.productVariation', 'purchaseRequestDetails.unit'];
    }
}
