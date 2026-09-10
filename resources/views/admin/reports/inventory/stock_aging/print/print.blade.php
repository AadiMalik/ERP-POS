@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.stock_aging'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.stock_aging'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead><tr><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_branch') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_value') }}</th><th>{{ __('reports.col_last_movement') }}</th><th>{{ __('reports.col_days_idle') }}</th><th>{{ __('reports.col_age_bucket') }}</th><th>{{ __('reports.col_class') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->branch_name ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->quantity ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->stock_value ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->last_movement_date ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->days_idle ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->age_bucket ?? '-') : '-' }}</td><td>{{ is_object($row) ? ($row->movement_class_label ?? '-') : '-' }}</td></tr>
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
