@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.serial_number_register'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.serial_number_register'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead><tr><th>{{ __('reports.col_serial_no') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_status') }}</th><th>{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_customer') }}</th><th>{{ __('reports.col_received_on') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->warehouse_name ?? '-' }}</td><td>{{ $row->status_label ?? '-' }}</td><td>{{ $row->avg_price ?? '-' }}</td><td>{{ $row->customer_name ?? '-' }}</td><td>{{ $row->date_created ? localDate($row->date_created) : '-' }}</td></tr>
            @empty
                <tr><td colspan="8">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
