<?php

namespace App\Exports\Orders;

use App\Models\ActivityLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OrderCorrectionReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $rows)
    {
    }

    public function collection()
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Date', 'Order No', 'Branch', 'Corrected By', 'Reason', 'Previous Total', 'New Total', 'Difference'];
    }

    public function map($row): array
    {
        /** @var ActivityLog $row */
        $old_total = (float) ($row->old_values['total'] ?? 0);
        $new_total = (float) ($row->new_values['total'] ?? 0);

        return [
            localDateTime($row->date_created),
            optional($row->order)->daily_order_id ?? $row->record_id,
            $row->branch->name ?? '',
            $row->causer->name ?? 'System',
            $row->new_values['reason'] ?? '-',
            round($old_total, 2),
            round($new_total, 2),
            round($new_total - $old_total, 2),
        ];
    }
}
