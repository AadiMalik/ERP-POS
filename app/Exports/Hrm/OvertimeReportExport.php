<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OvertimeReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Employee Code', 'Name', 'Department', 'Date', 'Working Hours', 'OT Hours', 'OT Rate', 'OT Amount'];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            $row->employee?->department?->name,
            localDate($row->date),
            $row->working_hours,
            $row->ot_hours,
            decimal($row->ot_rate),
            decimal($row->ot_amount),
        ];
    }
}
