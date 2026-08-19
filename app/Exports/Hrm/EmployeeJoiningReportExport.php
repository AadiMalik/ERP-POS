<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeJoiningReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Employee Code', 'Name', 'Department', 'Designation', 'Branch', 'Joining Date', 'Employment Type', 'Status'];
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->user?->name,
            $row->department?->name,
            $row->designation?->name,
            $row->branch?->name,
            localDate($row->joining_date),
            ucfirst(str_replace('_', ' ', (string) $row->employment_type)),
            ucfirst(str_replace('_', ' ', $row->status)),
        ];
    }
}
