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
        'title' => 'Stock Reconciliation & Adjustment Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead><tr><th>Doc No</th><th>Date</th><th>Type</th><th>Warehouse</th><th>Product</th><th>Variation</th><th>System</th><th>Physical</th><th>Diff Qty</th><th>Unit Cost</th><th>Diff Value</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->doc_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->doc_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->movement_type ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->system_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->physical_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->difference_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->difference_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->status ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="{{ count($rows) ? 1 : 12 }}">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>