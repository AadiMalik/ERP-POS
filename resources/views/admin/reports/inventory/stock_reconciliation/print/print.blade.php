@extends('layouts.print')
@section('title', 'Stock Reconciliation & Adjustment Report')
@section('content')
    <h3>Stock Reconciliation & Adjustment Report</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Doc No</th><th>Date</th><th>Type</th><th>Warehouse</th><th>Product</th><th>Variation</th><th>System</th><th>Physical</th><th>Diff Qty</th><th>Unit Cost</th><th>Diff Value</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->doc_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->doc_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->movement_type ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->system_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->physical_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->difference_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->difference_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->status ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection