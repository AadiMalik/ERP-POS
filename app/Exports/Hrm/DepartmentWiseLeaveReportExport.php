<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DepartmentWiseLeaveReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Department', 'Total Requests', 'Approved', 'Pending', 'Rejected', 'Total Days'];
    }

    public function map($row): array
    {
        return [
            $row->department,
            $row->total_requests,
            $row->approved_requests,
            $row->pending_requests,
            $row->rejected_requests,
            $row->total_days,
        ];
    }
}
