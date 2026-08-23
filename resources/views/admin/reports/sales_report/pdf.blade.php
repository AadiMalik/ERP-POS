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
        'title' => 'Sales Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Order No</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Warehouse</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Voucher</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
                <th class="text-right">Paid</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ optional($row->user)->name ?? 'Walk-in' }}</td>
                    <td>{{ optional($row->warehouse)->name ?? '' }}</td>
                    <td class="text-right">{{ currency($row->subtotal) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->voucher_discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td class="text-right">{{ currency($row->paid_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No records found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="9"><strong>Order Total</strong></td>
                <td class="text-right"><strong>{{ currency($summary['order_total']) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <table class="data-table" style="margin-top: 20px;">
        <tr>
            <td><strong>Order Subtotal (before discount/tax)</strong></td>
            <td class="text-right">{{ currency($summary['order_subtotal']) }}</td>
        </tr>
        <tr>
            <td><strong>Posted Sales Revenue (Ledger)</strong></td>
            <td class="text-right">{{ currency($summary['ledger_revenue']) }}</td>
        </tr>
        <tr>
            <td><strong>Variance</strong></td>
            <td class="text-right">{{ currency($summary['variance']) }}</td>
        </tr>
    </table>
</body>

</html>
