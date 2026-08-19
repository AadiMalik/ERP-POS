<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ShiftWiseAttendanceReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Shift', 'Timing', 'Employees', 'Present', 'Absent', 'Late', 'Leave', 'Total Working Hours'];
    }

    public function map($row): array
    {
        return [
            $row->shift_name,
            $row->timing,
            $row->employee_count,
            $row->present_count,
            $row->absent_count,
            $row->late_count,
            $row->leave_count,
            $row->total_working_hours,
        ];
    }
}
