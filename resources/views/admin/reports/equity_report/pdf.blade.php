@php
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
        'title' => __('reports.equity_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_account') }}</th>
                <th>{{ __('reports.col_sub_type') }}</th>
                <th class="text-right">Opening Balance</th>
                <th class="text-right">Period Debit</th>
                <th class="text-right">Period Credit</th>
                <th class="text-right">Closing Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->account_code }} {{ $row->account_name }}</td>
                    <td>{{ $row->account_subtype }}</td>
                    <td class="text-right">{{ currency($row->opening_balance) }} {{ $row->opening_balance_type }}</td>
                    <td class="text-right">{{ $row->period_debit > 0 ? currency($row->period_debit) : '' }}</td>
                    <td class="text-right">{{ $row->period_credit > 0 ? currency($row->period_credit) : '' }}</td>
                    <td class="text-right">{{ currency($row->closing_balance) }} {{ $row->closing_balance_type }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
