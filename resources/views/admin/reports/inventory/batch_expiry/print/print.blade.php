@extends('layouts.print')
@section('title', __('reports.batch_expiry'))
@section('content')
    <h3>{{ __('reports.batch_expiry') }}</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>{{ __('reports.col_batch') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_branch') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_avg_price') }}</th><th>{{ __('reports.col_value') }}</th><th>{{ __('reports.col_mfg_date') }}</th><th>{{ __('reports.col_expiry') }}</th><th>{{ __('reports.col_days_to_expiry') }}</th><th>{{ __('reports.col_status') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->batch_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->branch_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->avg_price ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->stock_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->manufacturing_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->expiry_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->days_to_expiry ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->status ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection