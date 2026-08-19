<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DeductionReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Employee Code', 'Name', 'Department', 'Title', 'Amount', 'Recurring', 'Effective From', 'Effective To', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            $row->title,
            decimal($row->amount),
            $row->is_recurring ? 'Yes' : 'No',
            localDate($row->effective_from),
            $row->effective_to ? localDate($row->effective_to) : '-',
            ucfirst($row->status),
        ];
    }
}
