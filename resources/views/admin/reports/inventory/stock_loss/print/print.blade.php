@extends('layouts.print')
@section('title', 'Stock Loss/Wastage/Damage Report')
@section('content')
    <h3>Stock Loss/Wastage/Damage Report</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Date</th><th>Type</th><th>Source</th><th>Reference</th><th>Warehouse</th><th>Product</th><th>Variation</th><th>Qty</th><th>Unit Cost</th><th>Value</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->transaction_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->transaction_type_label ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->source_module ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reference_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_price ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->value ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection