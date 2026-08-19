<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceRegisterReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        $days = $this->rows->first()->days ?? [];

        return array_merge(['Employee Code', 'Name', 'Department'], array_keys($days));
    }

    public function map($row): array
    {
        return array_merge([$row->employee_code, $row->name, $row->department], array_values($row->days));
    }
}
