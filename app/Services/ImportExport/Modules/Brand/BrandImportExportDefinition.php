<?php

namespace App\Services\ImportExport\Modules\Brand;

use App\Models\Brand;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use Illuminate\Database\Eloquent\Builder;

class BrandImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'brand';
    }

    public function label(): string
    {
        return 'Brands';
    }

    public function modelClass(): string
    {
        return Brand::class;
    }

    public function primaryKey(): string
    {
        return 'brand_id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Nestle', 'Unilever'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: false,
                enumValues: ['active', 'inactive'],
                sampleValues: ['active', 'active'],
                exportAccessor: 'status',
                notes: 'Defaults to "active" if left blank. The brand logo cannot be imported/exported (it is uploaded as an image file via the Brands screen).',
            ),
        ];
    }

    public function uniqueKeyColumns(): array
    {
        return ['name'];
    }

    protected function additionalCreateAttributes(ImportContext $ctx): array
    {
        return ['status' => 'active'];
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Brand::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business'];
    }
}
