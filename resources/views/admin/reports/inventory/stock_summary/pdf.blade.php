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
        'title' => 'Stock Summary Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead><tr><th>Product</th><th>Variation</th><th>Warehouse</th><th>Branch</th><th>Category</th><th>Qty</th><th>Reserved</th><th>Available</th><th>Avg Price</th><th>Value</th><th>Min Stock</th><th>Reorder</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->branch_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->category_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reserved_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->available_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->avg_price ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->stock_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->minimum_stock ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reorder_qty ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="{{ count($rows) ? 1 : 12 }}">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>