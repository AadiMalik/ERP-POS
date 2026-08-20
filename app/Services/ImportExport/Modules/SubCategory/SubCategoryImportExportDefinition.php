<?php

namespace App\Services\ImportExport\Modules\SubCategory;

use App\Models\Category;
use App\Models\SubCategory;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\RelationSpec;
use Illuminate\Database\Eloquent\Builder;

class SubCategoryImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'sub-category';
    }

    public function label(): string
    {
        return 'Sub Categories';
    }

    public function modelClass(): string
    {
        return SubCategory::class;
    }

    public function primaryKey(): string
    {
        return 'sub_category_id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Carbonated Drinks', 'Chips'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Category',
                attribute: 'category_id',
                type: 'relation',
                required: true,
                relation: new RelationSpec(Category::class, 'category', 'name', scopeToBusiness: true),
                sampleValues: ['Beverages', 'Snacks'],
                exportAccessor: fn ($m) => $m->category->name ?? '',
                notes: 'Copy the exact Category Name from the Categories list.',
            ),
            new ColumnDefinition(
                key: 'Status',
                attribute: 'status',
                type: 'enum',
                required: false,
                enumValues: ['active', 'inactive'],
                sampleValues: ['active', 'active'],
                exportAccessor: 'status',
                notes: 'Defaults to "active" if left blank. The sub-category logo cannot be imported/exported (it is uploaded as an image file via the Sub Categories screen).',
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
        $query = SubCategory::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'category'];
    }
}
