@extends('layouts.print')
@section('title', __('reports.serial_number_sold'))
@section('content')
    <h3>{{ __('reports.serial_number_sold') }}</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>{{ __('reports.col_serial_no') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_customer') }}</th><th>{{ __('reports.col_order_hash') }}</th><th>{{ __('reports.col_unit_cost') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->customer_name ?? '-' }}</td><td>{{ $row->order_daily_id ?? '-' }}</td><td>{{ $row->avg_price ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="6">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
