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
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 3px 5px; }
        table.data-table th { background-color: #f2f2f2; text-align: left; }
    </style>
</head>
<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.recipe_bom'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead><tr><th>{{ __('reports.col_finished_product') }}</th><th>{{ __('reports.col_finished_variation') }}</th><th>{{ __('reports.col_raw_product') }}</th><th>{{ __('reports.col_raw_variation') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_unit') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_line_cost') }}</th><th>{{ __('reports.col_available') }}</th><th>{{ __('reports.col_shortfall') }}</th><th>{{ __('reports.col_has_recipe') }}</th><th>{{ __('reports.col_updated') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->finished_product ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->finished_variation ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->raw_product ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->raw_variation ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->line_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->available_qty ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->shortfall ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->has_recipe ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->date_updated ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="{{ count($rows) ? 1 : 12 }}">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>