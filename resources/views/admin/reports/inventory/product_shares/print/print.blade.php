@php
    $platforms = \App\Services\Concrete\Api\ProductShareService::PLATFORMS;
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.product_shares'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.product_shares'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_product') }}</th>
                <th>{{ __('reports.col_category') }}</th>
                <th>{{ __('reports.col_brand') }}</th>
                <th>{{ __('reports.col_total_shares') }}</th>
                @foreach ($platforms as $platform)
                    <th>{{ __('reports.col_share_' . $platform) }}</th>
                @endforeach
                <th>{{ __('reports.col_last_shared') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) ? ($row->category_name ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) ? ($row->brand_name ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) ? ($row->total_shares ?? 0) : 0 }}</td>
                    @foreach ($platforms as $platform)
                        <td>{{ is_object($row) ? ($row->{$platform} ?? 0) : 0 }}</td>
                    @endforeach
                    <td>{{ is_object($row) && !empty($row->last_shared_at) ? localDateTime($row->last_shared_at) : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 5 + count($platforms) }}">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
