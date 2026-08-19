<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeMasterReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Email', 'Phone', 'Department', 'Designation', 'Shift', 'Branch',
            'Joining Date', 'Employment Type', 'Gender', 'Marital Status', 'National ID', 'Payment Method', 'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->user?->name,
            $row->user?->email,
            $row->user?->phone,
            $row->department?->name,
            $row->designation?->name,
            $row->shift?->name,
            $row->branch?->name,
            localDate($row->joining_date),
            ucfirst(str_replace('_', ' ', (string) $row->employment_type)),
            $row->gender,
            $row->marital_status,
            $row->national_id,
            $row->payment_method,
            ucfirst(str_replace('_', ' ', $row->status)),
        ];
    }
}
