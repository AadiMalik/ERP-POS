<?php

namespace App\Services\ImportExport\Modules\Shift;

use App\Models\Shift;
use App\Services\ImportExport\Support\AbstractImportExportDefinition;
use App\Services\ImportExport\Support\ColumnDefinition;
use App\Services\ImportExport\Support\ImportContext;
use App\Services\ImportExport\Support\ResolvedRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ShiftImportExportDefinition extends AbstractImportExportDefinition
{
    public function moduleKey(): string
    {
        return 'shift';
    }

    public function label(): string
    {
        return 'Shifts';
    }

    public function modelClass(): string
    {
        return Shift::class;
    }

    public function primaryKey(): string
    {
        return 'shift_id';
    }

    public function isBranchScoped(): bool
    {
        return true;
    }

    public function columns(): array
    {
        return [
            new ColumnDefinition(
                key: 'Name',
                attribute: 'name',
                type: 'string',
                required: true,
                sampleValues: ['Morning Shift', 'Night Shift'],
                exportAccessor: 'name',
            ),
            new ColumnDefinition(
                key: 'Start Time',
                attribute: 'start_time',
                type: 'string',
                required: true,
                sampleValues: ['09:00', '21:00'],
                exportAccessor: 'start_time',
                notes: 'Format HH:MM (24-hour).',
            ),
            new ColumnDefinition(
                key: 'End Time',
                attribute: 'end_time',
                type: 'string',
                required: true,
                sampleValues: ['17:00', '05:00'],
                exportAccessor: 'end_time',
                notes: 'Format HH:MM (24-hour). May be earlier than Start Time for an overnight shift.',
            ),
            new ColumnDefinition(
                key: 'Working Days',
                attribute: 'working_days',
                type: 'string',
                required: true,
                sampleValues: ['mon,tue,wed,thu,fri', 'mon,tue,wed,thu,fri,sat'],
                exportAccessor: fn ($m) => implode(',', $m->working_days ?? []),
                notes: 'Comma-separated 3-letter day codes: mon,tue,wed,thu,fri,sat,sun.',
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

    /**
     * working_days is stored as a JSON array (model cast) but arrives here as
     * a comma-separated string from the generic string column mapping -
     * convert it before delegating to the generic create/update logic.
     */
    public function save(ResolvedRow $row, ImportContext $ctx): array
    {
        if (!empty($row->attributes['working_days']) && is_string($row->attributes['working_days'])) {
            $row->attributes['working_days'] = array_values(array_filter(array_map(
                fn ($day) => Str::lower(trim($day)),
                explode(',', $row->attributes['working_days'])
            ), fn ($day) => $day !== ''));
        }

        return parent::save($row, $ctx);
    }

    public function exportQuery(array $filters, ImportContext $ctx): Builder
    {
        $query = Shift::query()->where('is_deleted', 0);

        if (!empty($filters['business_id'])) {
            $query->where('business_id', $filters['business_id']);
        }

        return $query->orderBy('name');
    }

    public function exportEagerLoads(): array
    {
        return ['business', 'branch'];
    }
}
