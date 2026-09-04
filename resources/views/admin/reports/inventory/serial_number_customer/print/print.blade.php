@extends('layouts.print')
@section('title', 'Customer-wise Serial Numbers')
@section('content')
    <h3>Customer-wise Serial Numbers</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Customer</th><th>Serial No</th><th>Product</th><th>Variation</th><th>Order #</th><th>Warranty Until</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->customer_name ?? '-' }}</td><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->order_daily_id ?? '-' }}</td><td>{{ $row->warranty_expires_at ? localDate($row->warranty_expires_at) : '-' }}</td></tr>
            @empty
                <tr><td colspan="6">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
