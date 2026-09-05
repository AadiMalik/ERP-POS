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
        'title' => __('reports.cancelled_orders'),
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
                <th>{{ __('reports.col_order_source') }}</th>
                <th>{{ __('reports.col_status') }}</th>
                <th class="text-right">{{ __('reports.col_amount') }}</th>
                <th>{{ __('reports.col_cancellation_reason') }}</th>
                <th>{{ __('reports.col_cancelled_by') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $history = $row->statusHistory->whereIn('to_status', ['cancelled', 'void'])->sortByDesc('date_created')->first();
                @endphp
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->user->name ?? 'Walk-in' }}</td>
                    <td>{{ $row->branch->name ?? '' }}</td>
                    <td>{{ $row->orderSource->name ?? '' }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td>{{ optional($history)->reason ?? '-' }}</td>
                    <td>{{ optional(optional($history)->changedby)->name ?? '-' }}</td>
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
