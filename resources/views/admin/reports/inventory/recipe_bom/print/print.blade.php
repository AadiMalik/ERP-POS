@extends('layouts.print')
@section('title', __('reports.recipe_bom'))
@section('content')
    <h3>{{ __('reports.recipe_bom') }}</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>{{ __('reports.col_finished_product') }}</th><th>{{ __('reports.col_finished_variation') }}</th><th>{{ __('reports.col_raw_product') }}</th><th>{{ __('reports.col_raw_variation') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_unit') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_line_cost') }}</th><th>{{ __('reports.col_available') }}</th><th>{{ __('reports.col_shortfall') }}</th><th>{{ __('reports.col_has_recipe') }}</th><th>{{ __('reports.col_updated') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->finished_product ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->finished_variation ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->raw_product ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->raw_variation ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->line_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->available_qty ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->shortfall ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->has_recipe ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->date_updated ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection