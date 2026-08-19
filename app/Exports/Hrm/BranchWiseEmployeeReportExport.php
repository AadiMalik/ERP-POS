<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BranchWiseEmployeeReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Branch', 'Total Employees', 'Active', 'On Leave', 'Resigned', 'Terminated'];
    }

    public function map($row): array
    {
        return [
            $row->name,
            $row->total_employees,
            $row->active_employees,
            $row->on_leave_employees,
            $row->resigned_employees,
            $row->terminated_employees,
        ];
    }
}
