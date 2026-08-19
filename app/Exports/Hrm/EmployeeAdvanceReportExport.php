<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeAdvanceReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Department', 'Amount', 'Request Date', 'Installments',
            'Installment Amount', 'Remaining Balance', 'Approver', 'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            decimal($row->amount),
            localDate($row->request_date),
            $row->installments_count,
            decimal($row->installment_amount),
            decimal($row->remaining_balance),
            $row->approver?->name,
            ucfirst($row->status),
        ];
    }
}
