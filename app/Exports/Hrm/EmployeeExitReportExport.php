<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeExitReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Department', 'Designation', 'Type', 'Request Date',
            'Notice Period (Days)', 'Last Working Date', 'Reason', 'Status', 'Final Settlement Amount',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            $row->employee?->designation?->name,
            ucfirst($row->type),
            localDate($row->request_date),
            $row->notice_period_days,
            localDate($row->last_working_date),
            $row->reason,
            ucfirst($row->status),
            decimal($row->final_settlement_amount),
        ];
    }
}
