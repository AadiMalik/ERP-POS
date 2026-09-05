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
        'title' => __('reports.stock_transfer'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead><tr><th>{{ __('reports.col_transfer_no') }}</th><th>{{ __('reports.col_date') }}</th><th>{{ __('reports.col_source_wh') }}</th><th>{{ __('reports.col_dest_wh') }}</th><th>{{ __('reports.col_source_branch') }}</th><th>{{ __('reports.col_dest_branch') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_value') }}</th><th>{{ __('reports.col_status') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->transfer_note_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->transfer_note_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->source_warehouse ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->destination_warehouse ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->source_branch ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->destination_branch ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->total_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->total_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->status ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="{{ count($rows) ? 1 : 12 }}">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>