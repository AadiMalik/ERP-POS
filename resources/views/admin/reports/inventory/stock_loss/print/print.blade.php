@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.stock_loss'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.stock_loss'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead><tr><th>{{ __('reports.col_date') }}</th><th>{{ __('reports.col_type') }}</th><th>{{ __('reports.col_source') }}</th><th>{{ __('reports.col_reference') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_value') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->transaction_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->transaction_type_label ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->source_module ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->reference_no ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->unit_price ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->value ?? '-') : '-' }}</td></tr>
            @empty
                <tr><td colspan="12">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
