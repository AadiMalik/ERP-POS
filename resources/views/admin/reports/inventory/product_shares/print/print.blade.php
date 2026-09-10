@php
    $platforms = \App\Services\Concrete\Api\ProductShareService::PLATFORMS;
@endphp
@extends('layouts.print')
@section('title', __('reports.product_shares'))
@section('content')
    <h3>{{ __('reports.product_shares') }}</h3>
    <table class="table table-bordered table-sm">
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
@endsection
