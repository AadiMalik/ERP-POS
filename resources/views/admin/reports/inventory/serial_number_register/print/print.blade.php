@extends('layouts.print')
@section('title', __('reports.serial_number_register'))
@section('content')
    <h3>{{ __('reports.serial_number_register') }}</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>{{ __('reports.col_serial_no') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_status') }}</th><th>{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_customer') }}</th><th>{{ __('reports.col_received_on') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->warehouse_name ?? '-' }}</td><td>{{ $row->status_label ?? '-' }}</td><td>{{ $row->avg_price ?? '-' }}</td><td>{{ $row->customer_name ?? '-' }}</td><td>{{ $row->date_created ? localDate($row->date_created) : '-' }}</td></tr>
            @empty
                <tr><td colspan="8">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
