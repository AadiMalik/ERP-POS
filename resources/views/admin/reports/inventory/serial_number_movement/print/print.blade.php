@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.serial_number_movement'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.serial_number_movement'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead><tr><th>{{ __('reports.col_date') }}</th><th>{{ __('reports.col_serial_no') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_event') }}</th><th>{{ __('reports.col_from') }}</th><th>{{ __('reports.col_to') }}</th><th>{{ __('reports.col_by') }}</th><th>{{ __('reports.col_notes') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr><td>{{ $row->date_created ? localDate($row->date_created) : '-' }}</td><td>{{ $row->serial_no ?? '-' }}</td><td>{{ $row->product_name ?? '-' }}</td><td>{{ $row->variation_name ?? '-' }}</td><td>{{ $row->event_label ?? '-' }}</td><td>{{ $row->from_warehouse_name ?? '-' }}</td><td>{{ $row->to_warehouse_name ?? '-' }}</td><td>{{ $row->createdby_name ?? '-' }}</td><td>{{ $row->notes ?? '-' }}</td></tr>
            @empty
                <tr><td colspan="9">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
