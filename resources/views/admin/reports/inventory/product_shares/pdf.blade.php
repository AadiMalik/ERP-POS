@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $platforms = \App\Services\Concrete\Api\ProductShareService::PLATFORMS;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 3px 4px; }
        table.data-table th { background-color: #f2f2f2; text-align: left; }
        table.data-table td.num, table.data-table th.num { text-align: right; }
    </style>
</head>
<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.product_shares'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_product') }}</th>
                <th>{{ __('reports.col_category') }}</th>
                <th>{{ __('reports.col_brand') }}</th>
                <th class="num">{{ __('reports.col_total_shares') }}</th>
                @foreach ($platforms as $platform)
                    <th class="num">{{ __('reports.col_share_' . $platform) }}</th>
                @endforeach
                <th>{{ __('reports.col_last_shared') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) ? ($row->category_name ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) ? ($row->brand_name ?? '-') : '-' }}</td>
                    <td class="num">{{ is_object($row) ? ($row->total_shares ?? 0) : 0 }}</td>
                    @foreach ($platforms as $platform)
                        <td class="num">{{ is_object($row) ? ($row->{$platform} ?? 0) : 0 }}</td>
                    @endforeach
                    <td>{{ is_object($row) && !empty($row->last_shared_at) ? localDateTime($row->last_shared_at) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 5 + count($platforms) }}">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
