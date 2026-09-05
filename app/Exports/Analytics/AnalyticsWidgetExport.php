<?php

namespace App\Exports\Analytics;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * One generic export for every Analytics tabular widget. Headings + rows
 * come from AnalyticsService::exportPayload() so this class stays free of
 * per-widget column knowledge.
 */
class AnalyticsWidgetExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        protected Collection $rows,
        protected array $headings
    ) {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }
}
