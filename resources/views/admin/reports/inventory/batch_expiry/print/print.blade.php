@extends('layouts.print')
@section('title', 'Batch/Lot & Expiry Report')
@section('content')
    <h3>Batch/Lot & Expiry Report</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Batch</th><th>Product</th><th>Variation</th><th>Warehouse</th><th>Branch</th><th>Qty</th><th>Avg Price</th><th>Value</th><th>Mfg Date</th><th>Expiry</th><th>Days to Expiry</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->batch_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->branch_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->avg_price ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->stock_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->manufacturing_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->expiry_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->days_to_expiry ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->status ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection