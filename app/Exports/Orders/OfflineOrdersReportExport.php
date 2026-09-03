<?php

namespace App\Exports\Orders;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OfflineOrdersReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return [
            'Order No', 'Date', 'Branch', 'Device', 'Customer', 'Status', 'Total', 'Offline Local ID', 'Last Sync At',
        ];
    }

    public function map($row): array
    {
        return [
            $row->daily_order_id,
            optional($row->order_date)->format('d-m-Y H:i'),
            $row->branch->name ?? '',
            $row->posDevice->name ?? '-',
            $row->user->name ?? 'Walk-in',
            ucfirst(str_replace('_', ' ', $row->status)),
            round($row->total, 2),
            $row->offline_local_id ?? '-',
            optional(optional($row->posDevice)->last_sync_at)->format('d-m-Y H:i') ?? '-',
        ];
    }
}
