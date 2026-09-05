@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')
@section('title', __('reports.fixed_asset_register'))
@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection
@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.fixed_asset_register'),
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
                <th>{{ __('reports.col_branch') }}</th>
                <th>{{ __('reports.col_purchase_date') }}</th>
                <th class="text-right">Cost</th>
                <th class="text-right">Book Value</th>
                <th class="text-right">Accum. Dep.</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->asset_code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->category }}</td>
                    <td>{{ $row->branch }}</td>
                    <td>{{ $row->purchase_date ? localDate($row->purchase_date) : '' }}</td>
                    <td class="text-right">{{ currency($row->purchase_cost) }}</td>
                    <td class="text-right">{{ currency($row->current_book_value) }}</td>
                    <td class="text-right">{{ currency($row->accumulated_depreciation) }}</td>
                    <td>{{ $row->depreciation_status }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
