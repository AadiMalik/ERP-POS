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
        'title' => 'Purchase Return Summary Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Group</th>
                <th class="text-right">Returns</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
                <th>Accounting Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->group_label }}</td>
                    <td class="text-right">{{ $row->return_count }}</td>
                    <td class="text-right">{{ decimal($row->total_qty) }}</td>
                    <td class="text-right">{{ currency($row->total_subtotal) }}</td>
                    <td class="text-right">{{ currency($row->total_discount) }}</td>
                    <td class="text-right">{{ currency($row->total_tax) }}</td>
                    <td class="text-right">{{ currency($row->total_amount) }}</td>
                    <td>{{ $row->posted_count }} Posted{{ $row->unposted_count > 0 ? ' / ' . $row->unposted_count . ' Unposted' : '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="2"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ decimal($rows->sum('total_qty')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('total_subtotal')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('total_discount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('total_tax')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('total_amount')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
