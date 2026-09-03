<?php

namespace App\Exports\Orders;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CancelledOrdersReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Order No', 'Date', 'Customer', 'Branch', 'Order Source', 'Status', 'Amount', 'Cancellation Reason', 'Cancelled By'];
    }

    public function map($row): array
    {
        $history = $row->statusHistory->whereIn('to_status', ['cancelled', 'void'])->sortByDesc('date_created')->first();

        return [
            $row->daily_order_id,
            optional($row->order_date)->format('d-m-Y H:i'),
            $row->user->name ?? 'Walk-in',
            $row->branch->name ?? '',
            $row->orderSource->name ?? '',
            ucfirst($row->status),
            round($row->total, 2),
            optional($history)->reason ?? '-',
            optional(optional($history)->changedby)->name ?? '-',
        ];
    }
}
