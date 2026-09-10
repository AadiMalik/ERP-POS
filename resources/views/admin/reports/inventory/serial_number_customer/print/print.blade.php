@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.serial_number_customer'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.serial_number_customer'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead><tr><th>{{ __('reports.col_customer') }}</th><th>{{ __('reports.col_serial_no') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_order_hash') }}</th><th>{{ __('reports.col_warranty_until') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->customer_name ?? '-' }}</td><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->order_daily_id ?? '-' }}</td><td>{{ $row->warranty_expires_at ? localDate($row->warranty_expires_at) : '-' }}</td></tr>
            @empty
                <tr><td colspan="6">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
