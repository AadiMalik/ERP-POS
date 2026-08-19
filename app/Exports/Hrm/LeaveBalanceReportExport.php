<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class LeaveBalanceReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Employee Code', 'Name', 'Department', 'Leave Type', 'Entitlement', 'Used', 'Remaining'];
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->name,
            $row->department,
            $row->leave_type,
            $row->entitlement,
            $row->used,
            $row->remaining,
        ];
    }
}
