@extends('layouts.print')
@section('title', 'Available Serial Numbers')
@section('content')
    <h3>Available Serial Numbers</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Serial No</th><th>Product</th><th>Variation</th><th>Warehouse</th><th>Unit Cost</th><th>Received On</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->warehouse_name ?? '-' }}</td><td>{{ $row->avg_price ?? '-' }}</td><td>{{ $row->date_created ? localDate($row->date_created) : '-' }}</td></tr>
            @empty
                <tr><td colspan="6">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
