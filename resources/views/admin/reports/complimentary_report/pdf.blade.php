@php
    use App\Enums\ComplimentaryStatus;
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $can_view_cost = Auth::user()->can('order.complimentary.view-cost');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 4px 6px; }
        table.data-table th { background-color: #f2f2f2; text-align: left; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.complimentary_report'),
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
                <th>{{ __('complimentary.status') }}</th>
                <th>{{ __('reports.col_product') }}</th>
                <th>{{ __('reports.col_variation') }}</th>
                <th class="text-right">{{ __('common.qty') }}</th>
                <th class="text-right">{{ __('complimentary.retail_value') }}</th>
                @if ($can_view_cost)
                    <th class="text-right">{{ __('complimentary.actual_cost') }}</th>
                @endif
                <th>{{ __('complimentary.reason') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ localDateTime($row->order_date) }}</td>
                    <td>{{ $row->customer_name ?? 'Walk-in' }}</td>
                    <td>{{ ComplimentaryStatus::getOptions()[$row->complimentary_status] ?? $row->complimentary_status }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->variation_name }}</td>
                    <td class="text-right">{{ decimal($row->quantity) }}</td>
                    <td class="text-right">{{ currency($row->complimentary_value) }}</td>
                    @if ($can_view_cost)
                        <td class="text-right">{{ currency((float) $row->cost_price * (float) $row->base_quantity) }}</td>
                    @endif
                    <td>{{ $row->line_reason_name ?: ($row->order_reason_name ?: '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $can_view_cost ? 10 : 9 }}" class="text-center">{{ __('common.no_records_found') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
