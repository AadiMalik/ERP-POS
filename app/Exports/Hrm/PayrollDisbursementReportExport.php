<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PayrollDisbursementReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Department', 'Period', 'Net Salary', 'Payment Method',
            'Bank Account Number', 'Payment Status', 'Paid On',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-',
            decimal($row->net_salary),
            $row->employee?->payment_method ? ucfirst($row->employee->payment_method) : '-',
            $row->employee?->bank_account_number,
            $row->status == 'paid' ? 'Paid' : 'Unpaid',
            $row->paid_at ? localDate($row->paid_at) : '-',
        ];
    }
}
