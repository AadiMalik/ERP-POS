<?php

namespace App\Services\ImportExport\Modules\OpeningStock;

use App\Enums\Status;
use App\Models\OpeningStock;
use App\Models\OpeningStockDetail;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ChildTableDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

class OpeningStockImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'opening-stock';
    }

    public function label(): string
    {
        return 'Opening Stock';
    }

    public function modelClass(): string
    {
        return OpeningStock::class;
    }

    public function primaryKey(): string
    {
        return 'opening_stock_id';
    }

    public function isBranchScoped(): bool
    {
        return false;
    }

    public function groupKeyColumn(): ?string
    {
        return 'Opening Stock No';
    }

    public function childRelationName(): ?string
    {
        return 'openingStockDetails';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Opening Stock No',
                attribute: 'opening_stock_no',
                type: 'string',
                required: true,
                sampleValues: ['OS-0001', 'OS-0002'],
                exportAccessor: 'opening_stock_no',
                notes: 'Repeat this exact value on every line item belonging to the same opening stock.',
            ),
            new ColumnDefinition(
                key: 'Date',
                attribute: 'opening_stock_date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-20', '2026-08-21'],
                exportAccessor: 'opening_stock_date',
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
            modelClass: OpeningStockDetail::class,
            primaryKey: 'opening_stock_detail_id',
            foreignKeyAttribute: 'opening_stock_id',
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
                    attribute: 'quantity',
                    type: 'decimal',
                    required: true,
                    sampleValues: ['10', '5'],
                ),
                new ColumnDefinition(
                    key: 'Unit Cost',
                    attribute: 'unit_cost',
                    type: 'decimal',
                    required: true,
                    sampleValues: ['50', '90'],
                ),
                new ColumnDefinition(
                    key: 'Batch No',
                    attribute: 'batch_no',
                    type: 'string',
                    required: false,
                    sampleValues: ['', ''],
                    notes: 'Only relevant for variations with batch/expiry tracking enabled.',
                ),
                new ColumnDefinition(
                    key: 'Expiry Date',
                    attribute: 'expiry_date',
                    type: 'date',
                    required: false,
                    sampleValues: ['', ''],
                    notes: 'Only relevant for variations with batch/expiry tracking enabled.',
                ),
            ],
            minChildren: 1,
        );
    }

    public function uniqueKeyColumns(): array
    {
        return ['opening_stock_no'];
    }

    protected function applyDomainValidation(ResolvedRow $row, ImportContext $ctx): void
    {
        $hasQuantity = false;

        foreach ($row->children as $child) {
            if ($child->action === 'invalid') {
                continue;
            }

            $quantity = (float) ($child->attributes['quantity'] ?? 0);

            if ($quantity < 0) {
                $child->action = 'invalid';
                $child->errors[] = ['column' => 'Quantity', 'value' => $quantity, 'reason' => 'Quantity cannot be negative.'];
                continue;
            }

            if ($quantity > 0) {
                $hasQuantity = true;
            }
        }

        if ($row->action !== 'invalid' && !$hasQuantity) {
            $row->action = 'invalid';
            $row->errors[] = [
                'column' => $this->groupKeyColumn(),
                'value' => $row->groupKey,
                'reason' => 'Please provide a quantity greater than zero for at least one product line.',
            ];
        }
    }

    /**
     * Mirrors OpeningStockService::save(): only the pending header + detail
     * rows are written here (delete-then-reinsert on update, same as the
     * manual Create/Edit screen). Approving an Opening Stock (which is what
     * actually posts it to ProductVariationStock/creates its Journal Entry)
     * stays a separate, manual, permission-gated action
     * (opening-stock.status) - import never auto-approves.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $warehouse = Warehouse::findOrFail($row->attributes['warehouse_id']);
        $businessId = $warehouse->business_id;
        $branchId = $warehouse->branch_id;

        if ($row->action === 'update') {
            $openingStock = OpeningStock::findOrFail($row->matchedId);

            if ($openingStock->status !== Status::PENDING) {
                throw new \Exception('Only pending opening stocks can be updated via import.');
            }

            $openingStock->update([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouse->warehouse_id,
                'opening_stock_date' => $row->attributes['opening_stock_date'],
                'updatedby_id' => $ctx->userId,
                'date_updated' => now(),
            ]);

            OpeningStockDetail::where('opening_stock_id', $openingStock->opening_stock_id)->delete();
            $created = false;
        } else {
            $openingStock = OpeningStock::create([
                'opening_stock_id' => generateUuid(),
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouse->warehouse_id,
                'opening_stock_no' => $row->attributes['opening_stock_no'],
                'opening_stock_date' => $row->attributes['opening_stock_date'],
                'status' => Status::PENDING,
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);
            $created = true;
        }

        $totalQuantity = 0;
        $totalValue = 0;

        foreach ($row->children as $child) {
            if ($child->action === 'invalid') {
                continue;
            }

            $quantity = (float) ($child->attributes['quantity'] ?? 0);
            $unitCost = (float) ($child->attributes['unit_cost'] ?? 0);
            $conversionFactor = 1;
            $baseQuantity = $quantity * $conversionFactor;
            $lineTotal = $baseQuantity * $unitCost;

            $totalQuantity += $baseQuantity;
            $totalValue += $lineTotal;

            OpeningStockDetail::create([
                'opening_stock_detail_id' => generateUuid(),
                'opening_stock_id' => $openingStock->opening_stock_id,
                'product_id' => $child->attributes['product_id'],
                'product_variation_id' => $child->attributes['product_variation_id'],
                'unit_id' => $child->attributes['unit_id'],
                'conversion_factor' => $conversionFactor,
                'quantity' => $quantity,
                'base_quantity' => $baseQuantity,
                'unit_cost' => $unitCost,
                'total_value' => $lineTotal,
                'batch_no' => $child->attributes['batch_no'] ?? null,
                'expiry_date' => $child->attributes['expiry_date'] ?? null,
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);
        }

        $openingStock->update([
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
        ]);

        return ['model' => $openingStock, 'created' => $created];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = OpeningStock::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('opening_stock_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['warehouse', 'openingStockDetails.product', 'openingStockDetails.productVariation', 'openingStockDetails.unit'];
    }
}
