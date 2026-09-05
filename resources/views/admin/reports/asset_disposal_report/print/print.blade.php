@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')
@section('title', __('reports.asset_disposal_report'))
@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection
@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.asset_disposal_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_code') }}</th>
                <th>{{ __('reports.col_name') }}</th>
                <th>{{ __('reports.col_category') }}</th>
                <th>{{ __('reports.col_disposal_date') }}</th>
                <th>{{ __('reports.col_type') }}</th>
                <th class="text-right">Sale Price</th>
                <th class="text-right">Book Value</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->asset_code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->category }}</td>
                    <td>{{ $row->disposal_date ? localDate($row->disposal_date) : '' }}</td>
                    <td>{{ $row->disposal_type }}</td>
                    <td class="text-right">{{ currency($row->sale_price) }}</td>
                    <td class="text-right">{{ currency($row->current_book_value) }}</td>
                    <td>{{ $row->depreciation_status }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="print-totals">
        <tr class="grand-total">
            <td>Total Sale Price</td>
            <td class="text-right">{{ currency($rows->sum('sale_price')) }}</td>
        </tr>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
