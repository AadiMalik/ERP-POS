<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeLeaveHistoryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Employee Code', 'Name', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Approver', 'Status', 'Remarks'];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->leaveType?->name,
            localDate($row->start_date),
            localDate($row->end_date),
            $row->days_count,
            $row->approver?->name,
            ucfirst($row->status),
            $row->remarks,
        ];
    }
}
