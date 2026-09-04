@extends('layouts.print')
@section('title', 'Stock Summary Report')
@section('content')
    <h3>Stock Summary Report</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Product</th><th>Variation</th><th>Warehouse</th><th>Branch</th><th>Category</th><th>Qty</th><th>Reserved</th><th>Available</th><th>Avg Price</th><th>Value</th><th>Min Stock</th><th>Reorder</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->branch_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->category_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reserved_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->available_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->avg_price ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->stock_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->minimum_stock ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reorder_qty ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection