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
        'title' => 'Branch-wise Sales Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Branch</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Gross</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Net</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->branch }}</td>
                    <td class="text-right">{{ $row->order_count }}</td>
                    <td class="text-right">{{ round($row->total_qty, 2) }}</td>
                    <td class="text-right">{{ currency($row->gross) }}</td>
                    <td class="text-right">{{ currency($row->discount) }}</td>
                    <td class="text-right">{{ currency($row->tax) }}</td>
                    <td class="text-right">{{ currency($row->net) }}</td>
                    <td class="text-right">{{ currency($row->paid_amount) }}</td>
                    <td class="text-right">{{ currency($row->due_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
