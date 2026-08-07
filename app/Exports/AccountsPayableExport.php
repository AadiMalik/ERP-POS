<?php

namespace App\Exports;

use App\Services\Concrete\Admin\Reports\AccountsPayableReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccountsPayableExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(protected Collection $invoices)
    {
    }

    public function collection()
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'Supplier Name',
            'Purchase Number',
            'Invoice Number',
            'Invoice Date',
            'Due Date',
            'Invoiced Amount',
            'Paid Amount',
            'Outstanding Amount',
            'Remaining Balance',
            'Status',
            'Payment References',
        ];
    }

    public function map($row): array
    {
        return [
            $row->supplier_name,
            $row->purchase_no,
            $row->invoice_number,
            localDate($row->invoice_date),
            localDate($row->due_date),
            decimal($row->invoiced_amount),
            decimal($row->paid_amount),
            decimal($row->outstanding_amount),
            decimal($row->remaining_balance),
            AccountsPayableReportService::STATUS_LABELS[$row->status] ?? ucfirst($row->status),
            $row->payment_references,
        ];
    }
}
