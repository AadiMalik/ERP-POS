@extends('layouts.print')
@section('title', 'Recipe/BOM Report')
@section('content')
    <h3>Recipe/BOM Report</h3>
    <table class="table table-bordered table-sm">
        <thead><tr><th>Finished Product</th><th>Finished Variation</th><th>Raw Product</th><th>Raw Variation</th><th>Qty</th><th>Unit</th><th>Warehouse</th><th>Unit Cost</th><th>Line Cost</th><th>Available</th><th>Shortfall</th><th>Has Recipe</th><th>Updated</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->finished_product ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->finished_variation ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->raw_product ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->raw_variation ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->line_cost ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->available_qty ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->shortfall ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->has_recipe ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->date_updated ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection