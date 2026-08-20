<?php

namespace App\Services\ImportExport\Modules\Category;

use App\Models\Category;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use Illuminate\Database\Eloquent\Builder;

class CategoryImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'category';
    }

    public function label(): string
    {
        return 'Categories';
    }

    public function modelClass(): string
    {
        return Category::class;
    }

    public function primaryKey(): string
    {
        return 'category_id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Beverages', 'Snacks'],
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
                notes: 'Defaults to "active" if left blank. The category logo cannot be imported/exported (it is uploaded as an image file via the Categories screen).',
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
        $query = Category::query()->where('is_deleted', 0);

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
