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
            font-size: 10px;
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
            padding: 3px 5px;
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
        'title' => __('reports.trial_balance'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_account') }}</th>
                <th>{{ __('reports.col_account_type') }}</th>
                <th class="text-right">Opening Debit</th>
                <th class="text-right">Opening Credit</th>
                <th class="text-right">Period Debit</th>
                <th class="text-right">Period Credit</th>
                <th class="text-right">Closing Debit</th>
                <th class="text-right">Closing Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->account_code }} {{ $row->account_name }}</td>
                    <td>{{ $row->account_type }}</td>
                    <td class="text-right">{{ $row->opening_debit > 0 ? currency($row->opening_debit) : '' }}</td>
                    <td class="text-right">{{ $row->opening_credit > 0 ? currency($row->opening_credit) : '' }}</td>
                    <td class="text-right">{{ $row->period_debit > 0 ? currency($row->period_debit) : '' }}</td>
                    <td class="text-right">{{ $row->period_credit > 0 ? currency($row->period_credit) : '' }}</td>
                    <td class="text-right">{{ $row->closing_debit > 0 ? currency($row->closing_debit) : '' }}</td>
                    <td class="text-right">{{ $row->closing_credit > 0 ? currency($row->closing_credit) : '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('opening_debit')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('opening_credit')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('period_debit')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('period_credit')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('closing_debit')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('closing_credit')) }}</strong></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
