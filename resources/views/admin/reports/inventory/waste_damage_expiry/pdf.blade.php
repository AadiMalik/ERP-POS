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
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #ccc; padding: 3px 5px; }
        table.data-table th { background-color: #f2f2f2; text-align: left; }
    </style>
</head>
<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Waste / Damage / Expiry Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="data-table">
        <thead><tr><th>Reference No</th><th>Date</th><th>Warehouse</th><th>Product</th><th>Variation</th><th>Batch</th><th>Expiry</th><th>Qty</th><th>Unit</th><th>Unit Cost</th><th>Value</th><th>Loss Type</th><th>Reason</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->reference_no ?? '-' }}</td>
                    <td>{{ $row->transaction_date ? localDate($row->transaction_date) : '-' }}</td>
                    <td>{{ $row->warehouse_name ?? '-' }}</td>
                    <td>{{ $row->product_name ?? '-' }}</td>
                    <td>{{ $row->variation_name ?? '-' }}</td>
                    <td>{{ $row->batch_no ?? '-' }}</td>
                    <td>{{ $row->expiry_date ? localDate($row->expiry_date) : '-' }}</td>
                    <td>{{ $row->quantity ?? '-' }}</td>
                    <td>{{ $row->unit_name ?? '-' }}</td>
                    <td>{{ $row->unit_cost ?? '-' }}</td>
                    <td>{{ $row->value ?? '-' }}</td>
                    <td>{{ $row->loss_type_label ?? '-' }}</td>
                    <td>{{ $row->loss_reason ?? '-' }}</td>
                    <td>{{ ucfirst($row->status ?? '-') }}</td>
                </tr>
            @empty
                <tr><td colspan="14">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
