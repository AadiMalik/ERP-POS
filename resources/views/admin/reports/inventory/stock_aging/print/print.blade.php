@extends('layouts.print')
@section('title', 'Stock Aging & Movement Report')
@section('content')
    <h3>Stock Aging & Movement Report</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Product</th><th>Variation</th><th>Warehouse</th><th>Branch</th><th>Qty</th><th>Value</th><th>Last Movement</th><th>Days Idle</th><th>Age Bucket</th><th>Class</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->branch_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->stock_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->last_movement_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->days_idle ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->age_bucket ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->movement_class_label ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection