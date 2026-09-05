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
        'title' => __('reports.order_detail'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_order_no') }}</th>
                <th>{{ __('reports.col_date_time') }}</th>
                <th>{{ __('reports.col_customer') }}</th>
                <th>{{ __('reports.col_branch') }}</th>
                <th>{{ __('reports.col_order_source') }}</th>
                <th>{{ __('reports.col_status') }}</th>
                <th>{{ __('reports.col_payment_status') }}</th>
                <th>{{ __('reports.col_product') }}</th>
                <th>{{ __('reports.col_variation') }}</th>
                <th>{{ __('reports.col_sku') }}</th>
                <th class="text-right">{{ __('reports.col_qty') }}</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">{{ __('reports.col_discount') }}</th>
                <th class="text-right">{{ __('reports.col_tax') }}</th>
                <th class="text-right">Delivery</th>
                <th class="text-right">Final Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $due = max(($row->order_total ?? 0) - ($row->order_paid_amount ?? 0), 0);
                    $payment_status = $due <= 0 ? 'paid' : (($row->order_paid_amount ?? 0) > 0 ? 'partially_paid' : 'unpaid');
                @endphp
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->customer_name ?? 'Walk-in' }}</td>
                    <td>{{ $row->branch_name ?? '' }}</td>
                    <td>{{ $row->order_source_name ?? '' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->order_status)) }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment_status)) }}</td>
                    <td>{{ $row->product_name ?? '' }}</td>
                    <td>{{ $row->variation_name ?? '' }}</td>
                    <td>{{ $row->sku ?? '' }}</td>
                    <td class="text-right">{{ decimal($row->quantity) }}</td>
                    <td class="text-right">{{ currency($row->unit_price) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency(0) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
