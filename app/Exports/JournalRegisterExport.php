<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class JournalRegisterExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
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
            'Date',
            'JV Number',
            'Journal Type',
            'Reference Type',
            'Reference Number',
            'Narration',
            'Total Debit',
            'Total Credit',
            'Status',
            'Posted By',
            'Created By',
        ];
    }

    public function map($row): array
    {
        return [
            localDate($row->entry_date),
            $row->entry_no,
            $row->journal_name ?? $row->journal_short,
            $row->source_type,
            $row->reference_no,
            $row->description,
            decimal($row->total_debit),
            decimal($row->total_credit),
            ucfirst($row->status),
            $row->posted_by_name ?? '-',
            $row->created_by_name ?? '-',
        ];
    }
}
