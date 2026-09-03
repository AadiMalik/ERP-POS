<?php

namespace App\Services\ImportExport\Modules\TransferNote;

use App\Enums\Status;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ProductVariationStock;
use App\Models\TransferNote;
use App\Models\TransferNoteDetail;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ChildTableDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;

class TransferNoteImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'transfer-note';
    }

    public function label(): string
    {
        return 'Transfer Notes';
    }

    public function modelClass(): string
    {
        return TransferNote::class;
    }

    public function primaryKey(): string
    {
        return 'transfer_note_id';
    }

    public function isBranchScoped(): bool
    {
        return false;
    }

    public function groupKeyColumn(): ?string
    {
        return 'Transfer Note No';
    }

    public function childRelationName(): ?string
    {
        return 'transferNoteDetails';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Transfer Note No',
                attribute: 'transfer_note_no',
                type: 'string',
                required: true,
                sampleValues: ['TRF-0001', 'TRF-0002'],
                exportAccessor: 'transfer_note_no',
                notes: 'Repeat this exact value on every line item belonging to the same transfer note.',
            ),
            new ColumnDefinition(
                key: 'Transfer Note Date',
                attribute: 'transfer_note_date',
                type: 'date',
                required: true,
                sampleValues: ['2026-08-20', '2026-08-21'],
                exportAccessor: 'transfer_note_date',
            ),
            new ColumnDefinition(
                key: 'Source Warehouse',
                attribute: 'source_warehouse_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Warehouse::class, 'warehouse', 'name', scopeToBusiness: true),
                sampleValues: ['Main Store', 'Main Store'],
                exportAccessor: fn ($m) => $m->sourceWarehouse->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Destination Warehouse',
                attribute: 'destination_warehouse_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Warehouse::class, 'warehouse', 'name', scopeToBusiness: true),
                sampleValues: ['Branch Store', 'Branch Store'],
                exportAccessor: fn ($m) => $m->destinationWarehouse->name ?? '',
            ),
            new ColumnDefinition(
                key: 'Reference',
                attribute: 'reference',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'reference',
            ),
            new ColumnDefinition(
                key: 'Description',
                attribute: 'description',
                type: 'string',
                required: false,
                sampleValues: ['', ''],
                exportAccessor: 'description',
            ),
        ];
    }

    public function childDefinition(): ?ChildTableDefinition
    {
        return new ChildTableDefinition(
            modelClass: TransferNoteDetail::class,
            primaryKey: 'transfer_note_detail_id',
            foreignKeyAttribute: 'transfer_note_id',
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
                    key: 'Transfer Quantity',
                    attribute: 'transfer_quantity',
                    type: 'decimal',
                    required: true,
                    sampleValues: ['10', '5'],
                ),
                new ColumnDefinition(
                    key: 'Conversion Factor',
                    attribute: 'conversion_factor',
                    type: 'decimal',
                    required: false,
                    sampleValues: ['1', '1'],
                    notes: 'Defaults to 1 if left blank.',
                ),
            ],
            minChildren: 1,
        );
    }

    public function uniqueKeyColumns(): array
    {
        return ['transfer_note_no'];
    }

    protected function applyDomainValidation(ResolvedRow $row, ImportContext $ctx): void
    {
        $sourceWarehouseId = $row->attributes['source_warehouse_id'] ?? null;
        $destinationWarehouseId = $row->attributes['destination_warehouse_id'] ?? null;

        if ($sourceWarehouseId && $destinationWarehouseId && $sourceWarehouseId === $destinationWarehouseId) {
            $row->action = 'invalid';
            $row->errors[] = ['column' => 'Source Warehouse', 'value' => null, 'reason' => 'Source and destination warehouse cannot be the same.'];
        }

        $hasQuantity = false;

        foreach ($row->children as $child) {
            if ($child->action === 'invalid' || !$sourceWarehouseId) {
                continue;
            }

            $productId = $child->attributes['product_id'] ?? null;
            $variationId = $child->attributes['product_variation_id'] ?? null;
            $transferQuantity = (float) ($child->attributes['transfer_quantity'] ?? 0);
            $conversionFactor = (float) ($child->attributes['conversion_factor'] ?? 1) ?: 1;
            $baseQuantity = $transferQuantity * $conversionFactor;

            $stock = ProductVariationStock::where('business_id', $ctx->businessId)
                ->where('warehouse_id', $sourceWarehouseId)
                ->where('product_id', $productId)
                ->where('product_variation_id', $variationId)
                ->first();
            $available = $stock->quantity ?? 0;

            if ($baseQuantity > $available) {
                $child->action = 'invalid';
                $child->errors[] = [
                    'column' => 'Transfer Quantity',
                    'value' => $transferQuantity,
                    'reason' => "Transfer quantity exceeds the available stock ({$available}) at the source warehouse.",
                ];
                continue;
            }

            if ($transferQuantity > 0) {
                $hasQuantity = true;
            }
        }

        if ($row->action !== 'invalid') {
            $validChildren = array_filter($row->children, fn (ResolvedRow $c) => $c->action !== 'invalid');
            if (empty($validChildren) || !$hasQuantity) {
                $row->action = 'invalid';
                $row->errors[] = [
                    'column' => $this->groupKeyColumn(),
                    'value' => $row->groupKey,
                    'reason' => 'Please provide a transfer quantity greater than zero for at least one product line.',
                ];
            }
        }
    }

    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        $sourceWarehouse = Warehouse::findOrFail($row->attributes['source_warehouse_id']);
        $destinationWarehouse = Warehouse::findOrFail($row->attributes['destination_warehouse_id']);
        $businessId = $sourceWarehouse->business_id;
        $branchId = $sourceWarehouse->branch_id;
        $destinationBranchId = $destinationWarehouse->branch_id;

        if ($row->action === 'update') {
            $transferNote = TransferNote::findOrFail($row->matchedId);

            if ($transferNote->status !== Status::DRAFT) {
                throw new \Exception('Only draft transfer notes can be updated via import.');
            }

            $transferNote->update([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'source_warehouse_id' => $sourceWarehouse->warehouse_id,
                'destination_warehouse_id' => $destinationWarehouse->warehouse_id,
                'destination_branch_id' => $destinationBranchId,
                'transfer_note_date' => $row->attributes['transfer_note_date'],
                'reference' => $row->attributes['reference'] ?? null,
                'description' => $row->attributes['description'] ?? null,
                'updatedby_id' => $ctx->userId,
                'date_updated' => now(),
            ]);

            TransferNoteDetail::where('transfer_note_id', $transferNote->transfer_note_id)->delete();
            $created = false;
        } else {
            $transferNote = TransferNote::create([
                'transfer_note_id' => generateUuid(),
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'source_warehouse_id' => $sourceWarehouse->warehouse_id,
                'destination_warehouse_id' => $destinationWarehouse->warehouse_id,
                'destination_branch_id' => $destinationBranchId,
                'transfer_note_no' => $row->attributes['transfer_note_no'],
                'transfer_note_date' => $row->attributes['transfer_note_date'],
                'reference' => $row->attributes['reference'] ?? null,
                'description' => $row->attributes['description'] ?? null,
                'status' => Status::DRAFT,
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

            $transferQuantity = (float) ($child->attributes['transfer_quantity'] ?? 0);
            $conversionFactor = (float) ($child->attributes['conversion_factor'] ?? 1) ?: 1;
            $baseQuantity = $transferQuantity * $conversionFactor;

            $stock = ProductVariationStock::where('business_id', $businessId)
                ->where('warehouse_id', $sourceWarehouse->warehouse_id)
                ->where('product_id', $child->attributes['product_id'])
                ->where('product_variation_id', $child->attributes['product_variation_id'])
                ->first();
            $availableQuantity = $stock->quantity ?? 0;
            $unitCost = $stock->avg_price ?? 0;
            $lineTotal = $baseQuantity * $unitCost;

            $totalQuantity += $baseQuantity;
            $totalValue += $lineTotal;

            TransferNoteDetail::create([
                'transfer_note_detail_id' => generateUuid(),
                'transfer_note_id' => $transferNote->transfer_note_id,
                'product_id' => $child->attributes['product_id'],
                'product_variation_id' => $child->attributes['product_variation_id'],
                'unit_id' => $child->attributes['unit_id'],
                'conversion_factor' => $conversionFactor,
                'available_quantity' => $availableQuantity,
                'transfer_quantity' => $transferQuantity,
                'base_quantity' => $baseQuantity,
                'unit_cost' => $unitCost,
                'total_value' => $lineTotal,
                'createdby_id' => $ctx->userId,
                'date_created' => now(),
            ]);
        }

        $transferNote->update([
            'total_quantity' => $totalQuantity,
            'total_value' => $totalValue,
        ]);

        return ['model' => $transferNote, 'created' => $created];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = TransferNote::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('transfer_note_date', 'desc');
    }

    public function exportEagerLoads(): array
    {
        return ['sourceWarehouse', 'destinationWarehouse', 'transferNoteDetails.product', 'transferNoteDetails.productVariation', 'transferNoteDetails.unit'];
    }
}
