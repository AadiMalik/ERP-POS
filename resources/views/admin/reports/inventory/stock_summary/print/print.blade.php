@extends('layouts.print')
@section('title', __('reports.stock_summary_report'))
@section('content')
    <h3>{{ __('reports.stock_summary_report') }}</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_branch') }}</th><th>{{ __('reports.col_category') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_reserved') }}</th><th>{{ __('reports.col_available') }}</th><th>{{ __('reports.col_avg_price') }}</th><th>{{ __('reports.col_value') }}</th><th>{{ __('reports.col_min_stock') }}</th><th>{{ __('reports.col_reorder') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->branch_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->category_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reserved_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->available_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->avg_price ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->stock_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->minimum_stock ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reorder_qty ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection