<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceSummaryReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Employee Code', 'Name', 'Department', 'Designation', 'Present', 'Absent', 'Late',
            'Half Day', 'Leave', 'Holiday', 'Early Checkout', 'Total Working Hours', 'Scheduled Working Days',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->name,
            $row->department,
            $row->designation,
            $row->present_count,
            $row->absent_count,
            $row->late_count,
            $row->half_day_count,
            $row->leave_count,
            $row->holiday_count,
            $row->early_checkout_count,
            $row->total_working_hours,
            $row->scheduled_working_days,
        ];
    }
}
