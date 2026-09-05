@php
    use App\Services\Concrete\Admin\Reports\AccountsPayableReportService;
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #ccc;
            padding: 4px 6px;
        }

        table.data-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.accounts_payable'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_supplier') }}</th>
                <th>{{ __('reports.col_purchase_no_dot') }}</th>
                <th>{{ __('reports.col_invoice_no_dot') }}</th>
                <th>{{ __('reports.col_invoice_date') }}</th>
                <th>{{ __('reports.col_due_date') }}</th>
                <th class="text-right">Invoiced</th>
                <th class="text-right">{{ __('reports.col_paid') }}</th>
                <th class="text-right">Outstanding</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $row)
                <tr>
                    <td>{{ $row->supplier_name }}</td>
                    <td>{{ $row->purchase_no }}</td>
                    <td>{{ $row->invoice_number }}</td>
                    <td>{{ localDate($row->invoice_date) }}</td>
                    <td>{{ localDate($row->due_date) }}</td>
                    <td class="text-right">{{ currency($row->invoiced_amount) }}</td>
                    <td class="text-right">{{ currency($row->paid_amount) }}</td>
                    <td class="text-right">{{ currency($row->outstanding_amount) }}</td>
                    <td>{{ AccountsPayableReportService::STATUS_LABELS[$row->status] ?? ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="5"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ currency($invoices->sum('invoiced_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($invoices->sum('paid_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($invoices->sum('outstanding_amount')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
