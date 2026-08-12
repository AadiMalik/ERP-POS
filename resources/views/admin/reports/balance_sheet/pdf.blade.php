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

        .grand-total {
            font-weight: bold;
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Balance Sheet',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [
            'As Of' => localDate($result['as_of_date']),
        ],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <tbody>
            <tr><th colspan="2">Assets</th></tr>
            <tr><th colspan="2">Current Assets</th></tr>
            @foreach ($result['current_assets'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Current Assets</td><td class="text-right">{{ currency($result['total_current_assets']) }}</td></tr>

            <tr><th colspan="2">Fixed Assets</th></tr>
            @foreach ($result['fixed_assets'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Fixed Assets</td><td class="text-right">{{ currency($result['total_fixed_assets']) }}</td></tr>

            @if ($result['other_assets']->isNotEmpty())
                <tr><th colspan="2">Other Assets</th></tr>
                @foreach ($result['other_assets'] as $row)
                    <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
                @endforeach
                <tr class="grand-total"><td>Total Other Assets</td><td class="text-right">{{ currency($result['total_other_assets']) }}</td></tr>
            @endif

            <tr class="grand-total"><td><strong>Total Assets</strong></td><td class="text-right"><strong>{{ currency($result['total_assets']) }}</strong></td></tr>
        </tbody>
    </table>

    <table class="data-table">
        <tbody>
            <tr><th colspan="2">Liabilities</th></tr>
            <tr><th colspan="2">Current Liabilities</th></tr>
            @foreach ($result['current_liabilities'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Current Liabilities</td><td class="text-right">{{ currency($result['total_current_liabilities']) }}</td></tr>

            @if ($result['long_term_liabilities']->isNotEmpty())
                <tr><th colspan="2">Long-term Liabilities</th></tr>
                @foreach ($result['long_term_liabilities'] as $row)
                    <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
                @endforeach
                <tr class="grand-total"><td>Total Long-term Liabilities</td><td class="text-right">{{ currency($result['total_long_term_liabilities']) }}</td></tr>
            @endif

            @if ($result['other_liabilities']->isNotEmpty())
                <tr><th colspan="2">Other Liabilities</th></tr>
                @foreach ($result['other_liabilities'] as $row)
                    <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
                @endforeach
                <tr class="grand-total"><td>Total Other Liabilities</td><td class="text-right">{{ currency($result['total_other_liabilities']) }}</td></tr>
            @endif

            <tr class="grand-total"><td>Total Liabilities</td><td class="text-right">{{ currency($result['total_liabilities']) }}</td></tr>

            <tr><th colspan="2">Equity</th></tr>
            @foreach ($result['equity'] as $row)
                <tr><td>{{ trim($row->account_code . ' ' . $row->account_name) }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Equity</td><td class="text-right">{{ currency($result['total_equity']) }}</td></tr>

            <tr class="grand-total"><td><strong>Total Liabilities &amp; Equity</strong></td><td class="text-right"><strong>{{ currency($result['total_liabilities_and_equity']) }}</strong></td></tr>
        </tbody>
    </table>
</body>

</html>
