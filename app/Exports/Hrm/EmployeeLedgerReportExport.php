<?php

namespace App\Exports\Hrm;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeeLedgerReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
        return ['Employee Code', 'Name', 'Entry Date', 'Type', 'Description', 'Debit', 'Credit', 'Balance After'];
    }

    public function map($row): array
    {
        return [
            $row->employee?->employee_code,
            $row->employee?->user?->name,
            localDate($row->entry_date),
            ucfirst($row->type),
            $row->description,
            $row->debit > 0 ? decimal($row->debit) : '',
            $row->credit > 0 ? decimal($row->credit) : '',
            decimal($row->balance_after),
        ];
    }
}
