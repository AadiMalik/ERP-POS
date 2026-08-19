<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeClearanceReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Employee Code', 'Name', 'Department', 'Exit Type', 'Area', 'Status', 'Cleared By', 'Cleared At', 'Remarks'];
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->name,
            $row->department,
            $row->exit_type,
            $row->area,
            ucfirst($row->status),
            $row->cleared_by,
            $row->cleared_at ? localDate($row->cleared_at) : '-',
            $row->remarks,
        ];
    }
}
