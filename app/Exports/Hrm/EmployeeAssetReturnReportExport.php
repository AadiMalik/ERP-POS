<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeAssetReturnReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Asset Tag', 'Asset Name', 'Employee', 'Department', 'Issue Date', 'Expected Return', 'Return Date', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->asset?->asset_tag,
            $row->asset?->name,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            localDate($row->issue_date),
            $row->expected_return_date ? localDate($row->expected_return_date) : '-',
            $row->return_date ? localDate($row->return_date) : '-',
            $row->is_overdue ? 'Overdue' : ucfirst($row->status),
        ];
    }
}
