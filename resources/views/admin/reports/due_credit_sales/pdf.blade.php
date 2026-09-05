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
        'title' => __('reports.due_credit_sales'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_order_no') }}</th>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_customer') }}</th>
                <th>{{ __('reports.col_branch') }}</th>
                <th class="text-right">Total Amount</th>
                <th class="text-right">Paid Amount</th>
                <th class="text-right">Due Amount</th>
                <th>{{ __('reports.col_payment_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $due = max(($row->total ?? 0) - ($row->paid_amount ?? 0), 0);
                    $payment_status = $due <= 0 ? 'paid' : (($row->paid_amount ?? 0) > 0 ? 'partially_paid' : 'unpaid');
                @endphp
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->user->name ?? 'Walk-in' }}</td>
                    <td>{{ $row->branch->name ?? '' }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td class="text-right">{{ currency($row->paid_amount) }}</td>
                    <td class="text-right">{{ currency($due) }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment_status)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
