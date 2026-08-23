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
        'title' => 'Service Payment Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Payment No</th>
                <th>Party</th>
                <th>Reference</th>
                <th>Method</th>
                <th class="text-right">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->payment_date) }}</td>
                    <td>{{ $row->payment_type }}</td>
                    <td>{{ $row->payment_no }}</td>
                    <td>{{ $row->party_name }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $row->payment_method)) }}</td>
                    <td class="text-right">{{ currency($row->net_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="data-table" style="margin-top: 20px;">
        <tr>
            <td><strong>Total Receipts</strong></td>
            <td class="text-right">{{ currency($rows->where('payment_type', 'Receipt')->sum('net_amount')) }}</td>
        </tr>
        <tr>
            <td><strong>Total Payments</strong></td>
            <td class="text-right">{{ currency($rows->where('payment_type', 'Payment')->sum('net_amount')) }}</td>
        </tr>
        <tr>
            <td><strong>Net Cash Flow</strong></td>
            <td class="text-right">
                {{ currency($rows->where('payment_type', 'Receipt')->sum('net_amount') - $rows->where('payment_type', 'Payment')->sum('net_amount')) }}
            </td>
        </tr>
    </table>
</body>

</html>
