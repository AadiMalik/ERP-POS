@extends('layouts.print')
@section('title', 'Waste / Damage / Expiry Report')
@section('content')
    <h3>Waste / Damage / Expiry Report</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Reference No</th><th>Date</th><th>Warehouse</th><th>Product</th><th>Variation</th><th>Batch</th><th>Expiry</th><th>Qty</th><th>Unit</th><th>Unit Cost</th><th>Value</th><th>Loss Type</th><th>Reason</th><th>Status</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->reference_no ?? '-' }}</td>
                    <td>{{ $row->transaction_date ? localDate($row->transaction_date) : '-' }}</td>
                    <td>{{ $row->warehouse_name ?? '-' }}</td>
                    <td>{{ $row->product_name ?? '-' }}</td>
                    <td>{{ $row->variation_name ?? '-' }}</td>
                    <td>{{ $row->batch_no ?? '-' }}</td>
                    <td>{{ $row->expiry_date ? localDate($row->expiry_date) : '-' }}</td>
                    <td>{{ $row->quantity ?? '-' }}</td>
                    <td>{{ $row->unit_name ?? '-' }}</td>
                    <td>{{ $row->unit_cost ?? '-' }}</td>
                    <td>{{ $row->value ?? '-' }}</td>
                    <td>{{ $row->loss_type_label ?? '-' }}</td>
                    <td>{{ $row->loss_reason ?? '-' }}</td>
                    <td>{{ ucfirst($row->status ?? '-') }}</td>
                </tr>
            @empty
                <tr><td colspan="14">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
