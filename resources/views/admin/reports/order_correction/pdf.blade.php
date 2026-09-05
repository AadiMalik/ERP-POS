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
        'title' => __('reports.order_correction'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_order_no') }}</th>
                <th>{{ __('reports.col_branch') }}</th>
                <th>{{ __('reports.col_corrected_by') }}</th>
                <th>{{ __('reports.col_reason') }}</th>
                <th class="text-right">Previous Total</th>
                <th class="text-right">New Total</th>
                <th class="text-right">Difference</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $old_total = (float) ($row->old_values['total'] ?? 0);
                    $new_total = (float) ($row->new_values['total'] ?? 0);
                @endphp
                <tr>
                    <td>{{ localDateTime($row->date_created) }}</td>
                    <td>{{ optional($row->order)->daily_order_id ?? $row->record_id }}</td>
                    <td>{{ $row->branch->name ?? '' }}</td>
                    <td>{{ $row->causer->name ?? 'System' }}</td>
                    <td>{{ $row->new_values['reason'] ?? '-' }}</td>
                    <td class="text-right">{{ currency(round($old_total, 2)) }}</td>
                    <td class="text-right">{{ currency(round($new_total, 2)) }}</td>
                    <td class="text-right">{{ currency(round($new_total - $old_total, 2)) }}</td>
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
