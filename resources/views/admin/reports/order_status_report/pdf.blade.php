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
        'title' => __('reports.order_status_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_status') }}</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Gross Sales</th>
                <th class="text-right">{{ __('reports.col_discount') }}</th>
                <th class="text-right">{{ __('reports.col_tax') }}</th>
                <th class="text-right">Net Sales</th>
                <th class="text-right">{{ __('reports.col_paid') }}</th>
                <th class="text-right">{{ __('reports.col_due') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                    <td class="text-right">{{ $row->order_count }}</td>
                    <td class="text-right">{{ currency($row->gross) }}</td>
                    <td class="text-right">{{ currency($row->discount) }}</td>
                    <td class="text-right">{{ currency($row->tax) }}</td>
                    <td class="text-right">{{ currency($row->net) }}</td>
                    <td class="text-right">{{ currency($row->paid) }}</td>
                    <td class="text-right">{{ currency(max($row->net - $row->paid, 0)) }}</td>
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
