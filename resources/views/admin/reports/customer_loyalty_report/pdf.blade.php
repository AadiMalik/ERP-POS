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
        'title' => __('reports.customer_loyalty_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_customer') }}</th>
                <th>{{ __('reports.col_reference') }}</th>
                <th>{{ __('reports.col_transaction_type') }}</th>
                <th class="text-right">Points</th>
                <th class="text-right">Monetary Value</th>
                <th>{{ __('reports.col_date') }}</th>
                <th class="text-right">Balance After</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->customer_name ?? 'N/A' }}</td>
                    <td>
                        @if ('order' === $row->reference_type && $row->reference_order_no)
                            {{ $row->reference_order_no }}
                        @elseif ($row->reference_type)
                            {{ ucfirst(str_replace('_', ' ', $row->reference_type)) }}{{ $row->reference_id ? ' #' . $row->reference_id : '' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ ucfirst($row->transaction_type) }}</td>
                    <td class="text-right">{{ decimal($row->points) }}</td>
                    <td class="text-right">{{ $row->monetary_value !== null ? currency($row->monetary_value) : '-' }}</td>
                    <td>{{ optional($row->date_created)->format('d-m-Y H:i') }}</td>
                    <td class="text-right">{{ decimal($row->available_balance_after) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
