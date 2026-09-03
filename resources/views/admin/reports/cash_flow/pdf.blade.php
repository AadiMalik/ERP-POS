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
        'title' => 'Cash Flow Statement',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [
            'Period' => localDate($result['start_date']) . ' to ' . localDate($result['end_date']),
        ],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Particulars</th>
                <th class="text-right">Inflow</th>
                <th class="text-right">Outflow</th>
                <th class="text-right">Net</th>
            </tr>
        </thead>
        <tbody>
            <tr><th colspan="4">Cash flows from operating activities</th></tr>
            @foreach ($result['operating'] as $row)
                <tr>
                    <td>{{ $row->label }}</td>
                    <td class="text-right">{{ $row->inflow > 0 ? currency($row->inflow) : '' }}</td>
                    <td class="text-right">{{ $row->outflow > 0 ? currency($row->outflow) : '' }}</td>
                    <td class="text-right">{{ currency($row->amount) }}</td>
                </tr>
            @endforeach
            <tr class="grand-total">
                <td>Net cash from operating activities</td>
                <td></td><td></td>
                <td class="text-right">{{ currency($result['net_operating']) }}</td>
            </tr>

            <tr><th colspan="4">Cash flows from investing activities</th></tr>
            @foreach ($result['investing'] as $row)
                <tr>
                    <td>{{ $row->label }}</td>
                    <td class="text-right">{{ $row->inflow > 0 ? currency($row->inflow) : '' }}</td>
                    <td class="text-right">{{ $row->outflow > 0 ? currency($row->outflow) : '' }}</td>
                    <td class="text-right">{{ currency($row->amount) }}</td>
                </tr>
            @endforeach
            <tr class="grand-total">
                <td>Net cash from investing activities</td>
                <td></td><td></td>
                <td class="text-right">{{ currency($result['net_investing']) }}</td>
            </tr>

            <tr><th colspan="4">Cash flows from financing activities</th></tr>
            @foreach ($result['financing'] as $row)
                <tr>
                    <td>{{ $row->label }}</td>
                    <td class="text-right">{{ $row->inflow > 0 ? currency($row->inflow) : '' }}</td>
                    <td class="text-right">{{ $row->outflow > 0 ? currency($row->outflow) : '' }}</td>
                    <td class="text-right">{{ currency($row->amount) }}</td>
                </tr>
            @endforeach
            <tr class="grand-total">
                <td>Net cash from financing activities</td>
                <td></td><td></td>
                <td class="text-right">{{ currency($result['net_financing']) }}</td>
            </tr>

            <tr class="grand-total">
                <td><strong>Net increase / (decrease) in cash &amp; bank</strong></td>
                <td class="text-right">{{ currency($result['total_inflows']) }}</td>
                <td class="text-right">{{ currency($result['total_outflows']) }}</td>
                <td class="text-right"><strong>{{ currency($result['net_increase']) }}</strong></td>
            </tr>
            <tr class="grand-total">
                <td>Opening cash &amp; bank balance</td>
                <td></td><td></td>
                <td class="text-right">{{ currency($result['opening_cash']) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Closing cash &amp; bank balance</td>
                <td></td><td></td>
                <td class="text-right">{{ currency($result['closing_cash']) }}</td>
            </tr>

            <tr><th colspan="4">Reconciliation</th></tr>
            <tr>
                <td>Opening balance + net cash movement</td>
                <td></td><td></td>
                <td class="text-right">{{ currency($result['reconciled_closing']) }}</td>
            </tr>
            <tr>
                <td>Actual closing cash &amp; bank balance</td>
                <td></td><td></td>
                <td class="text-right">{{ currency($result['closing_cash']) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Difference</td>
                <td></td><td></td>
                <td class="text-right">{{ currency($result['reconciliation_difference']) }}</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
