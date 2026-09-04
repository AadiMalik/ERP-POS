@extends('layouts.print')
@section('title', 'Serial Number Movement History')
@section('content')
    <h3>Serial Number Movement History</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Date</th><th>Serial No</th><th>Product</th><th>Variation</th><th>Event</th><th>From</th><th>To</th><th>By</th><th>Notes</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->date_created ? localDate($row->date_created) : '-' }}</td><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->event_label ?? '-' }}</td><td>{{ $row->from_warehouse_name ?? '-' }}</td><td>{{ $row->to_warehouse_name ?? '-' }}</td><td>{{ $row->createdby_name ?? '-' }}</td><td>{{ $row->notes ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="9">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
