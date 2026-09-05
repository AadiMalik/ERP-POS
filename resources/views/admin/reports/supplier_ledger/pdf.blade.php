@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($result['supplier']->business_id);
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
        'business' => $result['supplier']->business,
        'branch' => $result['supplier']->branch,
        'title' => __('reports.supplier_ledger'),
        'doc_no' => $result['supplier']->code,
        'doc_date' => localDate(now()),
        'reference' => [
            'Supplier' => $result['supplier']->name ?? 'N/A',
            'Period' => (!empty($result['start_date']) ? localDate($result['start_date']) : 'Beginning') . ' to ' . (!empty($result['end_date']) ? localDate($result['end_date']) : 'Today'),
        ],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_document_date') }}</th>
                <th>{{ __('reports.col_voucher_type') }}</th>
                <th>{{ __('reports.col_voucher_no') }}</th>
                <th>{{ __('reports.col_reference_no') }}</th>
                <th>{{ __('reports.col_description') }}</th>
                <th class="text-right">{{ __('reports.col_debit') }}</th>
                <th class="text-right">{{ __('reports.col_credit') }}</th>
                <th class="text-right">Running Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7"><strong>Opening Balance</strong></td>
                <td class="text-right"><strong>{{ currency($result['opening_balance']) }} {{ $result['opening_balance_type'] }}</strong></td>
            </tr>
            @forelse ($result['rows'] as $row)
                <tr>
                    <td>{{ localDate($row->entry_date) }}</td>
                    <td>{{ $row->voucher_name ?? $row->source_type ?? '' }}</td>
                    <td>{{ $row->entry_no }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ $row->detail_description ?: $row->entry_description }}</td>
                    <td class="text-right">{{ $row->debit > 0 ? currency($row->debit) : '' }}</td>
                    <td class="text-right">{{ $row->credit > 0 ? currency($row->credit) : '' }}</td>
                    <td class="text-right">{{ currency($row->running_balance) }} {{ $row->running_balance_type }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No transactions found for the selected period</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="5"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ currency($result['total_debit']) }}</strong></td>
                <td class="text-right"><strong>{{ currency($result['total_credit']) }}</strong></td>
                <td class="text-right"><strong>{{ currency($result['closing_balance']) }} {{ $result['closing_balance_type'] }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
