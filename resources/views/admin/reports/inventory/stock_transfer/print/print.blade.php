@extends('layouts.print')
@section('title', 'Stock Transfer Report')
@section('content')
    <h3>Stock Transfer Report</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Transfer No</th><th>Date</th><th>Source WH</th><th>Dest WH</th><th>Source Branch</th><th>Dest Branch</th><th>Qty</th><th>Value</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->transfer_note_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->transfer_note_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->source_warehouse ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->destination_warehouse ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->source_branch ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->destination_branch ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->total_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->total_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->status ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection