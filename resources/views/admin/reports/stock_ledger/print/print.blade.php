@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.stock_ledger'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.stock_ledger'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_source_module') }}</th>
                <th>{{ __('reports.col_reference_no') }}</th>
                <th>{{ __('reports.col_warehouse') }}</th>
                <th>{{ __('reports.col_product') }}</th>
                <th>{{ __('reports.col_variation') }}</th>
                <th>{{ __('reports.col_movement_type') }}</th>
                <th class="text-right">Qty In</th>
                <th class="text-right">Qty Out</th>
                <th class="text-right">{{ __('reports.col_unit_cost') }}</th>
                <th class="text-right">{{ __('reports.col_value') }}</th>
                <th class="text-right">{{ __('reports.col_balance') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->transaction_date) }}</td>
                    <td>{{ $row->source_module }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ $row->warehouse_name }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->variation_name }}</td>
                    <td>{{ $row->transaction_type_label }}</td>
                    <td class="text-right">{{ $row->quantity_in > 0 ? decimal($row->quantity_in) : '' }}</td>
                    <td class="text-right">{{ $row->quantity_out > 0 ? decimal($row->quantity_out) : '' }}</td>
                    <td class="text-right">{{ currency($row->unit_price) }}</td>
                    <td class="text-right">{{ currency($row->value) }}</td>
                    <td class="text-right">{{ decimal($row->quantity_after) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Qty In</td>
            <td class="text-right">{{ decimal($rows->sum('quantity_in')) }}</td>
        </tr>
        <tr>
            <td>Total Qty Out</td>
            <td class="text-right">{{ decimal($rows->sum('quantity_out')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Value</td>
            <td class="text-right">{{ currency($rows->sum('value')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
