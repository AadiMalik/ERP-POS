<?php

namespace App\Services\ImportExport\Modules\Discount;

use App\Models\Discount;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use Illuminate\Database\Eloquent\Builder;

class DiscountImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'discount';
    }

    public function label(): string
    {
        return 'Discounts';
    }

    public function modelClass(): string
    {
        return Discount::class;
    }

    public function primaryKey(): string
    {
        return 'discount_id';
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Weekend Sale', 'Loyalty Discount'],
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
                notes: '"percent" applies Value as a percentage; "fixed" applies Value as a flat amount.',
            ),
            new ColumnDefinition(
                key: 'Value',
                attribute: 'value',
                type: 'decimal',
                required: true,
                sampleValues: ['10', '5.5'],
                exportAccessor: 'value',
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
        $query = Discount::query()->where('is_deleted', 0);

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
