@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')
@section('title', __('reports.depreciation_report'))
@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection
@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.depreciation_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])
    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_period') }}</th>
                <th>{{ __('reports.col_asset') }}</th>
                <th>{{ __('reports.col_branch') }}</th>
                <th class="text-right">Previous</th>
                <th class="text-right">{{ __('reports.col_amount') }}</th>
                <th class="text-right">New Value</th>
                <th class="text-right">Accumulated</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->depreciation_date ? localDate($row->depreciation_date) : '' }}</td>
                    <td>{{ $row->period_key }}</td>
                    <td>{{ $row->asset_code }} {{ $row->asset_name }}</td>
                    <td>{{ $row->branch }}</td>
                    <td class="text-right">{{ currency($row->previous_value) }}</td>
                    <td class="text-right">{{ currency($row->depreciation_amount) }}</td>
                    <td class="text-right">{{ currency($row->new_value) }}</td>
                    <td class="text-right">{{ currency($row->accumulated_depreciation) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="print-totals">
        <tr class="grand-total">
            <td>Total Depreciation</td>
            <td class="text-right">{{ currency($rows->sum('depreciation_amount')) }}</td>
        </tr>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
