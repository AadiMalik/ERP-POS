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
        'title' => 'Sale Service Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Group</th>
                <th class="text-right">Transactions</th>
                <th class="text-right">Sale Amount</th>
                <th class="text-right">Sale Return Amount</th>
                <th class="text-right">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->group_label }}</td>
                    <td class="text-right">{{ $row->transaction_count }}</td>
                    <td class="text-right">{{ currency($row->sale_amount) }}</td>
                    <td class="text-right">{{ currency($row->sale_return_amount) }}</td>
                    <td class="text-right">{{ currency($row->net_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="data-table" style="margin-top: 20px;">
        <tr>
            <td><strong>Total Sale Amount</strong></td>
            <td class="text-right">{{ currency($rows->sum('sale_amount')) }}</td>
        </tr>
        <tr>
            <td><strong>Total Sale Return Amount</strong></td>
            <td class="text-right">{{ currency($rows->sum('sale_return_amount')) }}</td>
        </tr>
        <tr>
            <td><strong>Net Amount</strong></td>
            <td class="text-right">{{ currency($rows->sum('net_amount')) }}</td>
        </tr>
    </table>
</body>

</html>
