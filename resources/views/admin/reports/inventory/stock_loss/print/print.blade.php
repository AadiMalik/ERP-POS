@extends('layouts.print')
@section('title', __('reports.stock_loss'))
@section('content')
    <h3>{{ __('reports.stock_loss') }}</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>{{ __('reports.col_date') }}</th><th>{{ __('reports.col_type') }}</th><th>{{ __('reports.col_source') }}</th><th>{{ __('reports.col_reference') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_value') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->transaction_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->transaction_type_label ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->source_module ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reference_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_price ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->value ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection