<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AssetAllocationReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Asset Tag', 'Asset Name', 'Employee Code', 'Name', 'Department', 'Issue Date',
            'Expected Return Date', 'Return Date', 'Condition on Issue', 'Condition on Return', 'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->asset?->asset_tag,
            $row->asset?->name,
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            localDate($row->issue_date),
            $row->expected_return_date ? localDate($row->expected_return_date) : '-',
            $row->return_date ? localDate($row->return_date) : '-',
            ucfirst($row->condition_on_issue),
            $row->condition_on_return ? ucfirst($row->condition_on_return) : '-',
            ucfirst($row->status),
        ];
    }
}
