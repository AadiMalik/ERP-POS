<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ShiftAssignmentReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Employee Code', 'Name', 'Department', 'Shift', 'Timing', 'Working Days'];
    }

    public function map($row): array
    {
        return [
            $row->employee_code,
            $row->user?->name,
            $row->department?->name,
            $row->shift?->name ?? 'Unassigned',
            $row->shift ? (date('h:i A', strtotime($row->shift->start_time)) . ' - ' . date('h:i A', strtotime($row->shift->end_time))) : '-',
            $row->shift?->working_days ? implode(', ', array_map('ucfirst', $row->shift->working_days)) : '-',
        ];
    }
}
