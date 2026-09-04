@extends('layouts.print')
@section('title', 'Sold Serial Numbers')
@section('content')
    <h3>Sold Serial Numbers</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Serial No</th><th>Product</th><th>Variation</th><th>Customer</th><th>Order #</th><th>Unit Cost</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->customer_name ?? '-' }}</td><td>{{ $row->order_daily_id ?? '-' }}</td><td>{{ $row->avg_price ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="6">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
