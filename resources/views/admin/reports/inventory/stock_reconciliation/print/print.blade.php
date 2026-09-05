@extends('layouts.print')
@section('title', __('reports.stock_reconciliation'))
@section('content')
    <h3>{{ __('reports.stock_reconciliation') }}</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>{{ __('reports.col_doc_no') }}</th><th>{{ __('reports.col_date') }}</th><th>{{ __('reports.col_type') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_system') }}</th><th>{{ __('reports.col_physical') }}</th><th>{{ __('reports.col_diff_qty') }}</th><th>{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_diff_value') }}</th><th>{{ __('reports.col_status') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->doc_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->doc_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->movement_type ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->system_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->physical_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->difference_quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->difference_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->status ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection