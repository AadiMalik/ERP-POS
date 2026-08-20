<?php

namespace App\Services\ImportExport\Modules\Asset;

use App\Models\Asset;
use App\Models\Product;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use Illuminate\Database\Eloquent\Builder;

class AssetImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'asset';
    }

    public function label(): string
    {
        return 'Assets';
    }

    public function modelClass(): string
    {
        return Asset::class;
    }

    public function primaryKey(): string
    {
        return 'asset_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Asset Tag',
                attribute: 'asset_tag',
                type: 'string',
                required: true,
                sampleValues: ['AST-0001', 'AST-0002'],
                exportAccessor: 'asset_tag',
            ),
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Dell Laptop', 'Office Chair'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Product',
                attribute: 'product_id',
                type: 'relation',
                required: false,
                relation: new RelationSpec(Product::class, 'product', 'name', scopeToBusiness: true),
                sampleValues: ['', ''],
                exportAccessor: fn ($m) => $m->product->name ?? '',
                notes: 'Optional link to an existing Product. Copy the exact Product Name from the Products list.',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: false,
                enumValues: ['available', 'allocated', 'maintenance', 'retired'],
                sampleValues: ['available', ''],
                exportAccessor: 'status',
                notes: 'Defaults to "available" if left blank.',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['asset_tag'];
    }

    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        return ['status' => 'available'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Asset::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('asset_tag');
    }

    public function exportEagerLoads(): array
    {
        return ['product'];
    }
}
