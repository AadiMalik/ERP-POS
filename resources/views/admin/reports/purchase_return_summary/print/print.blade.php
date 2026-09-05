@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.purchase_return_summary'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.purchase_return_summary'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_group') }}</th>
                <th class="text-right">Returns</th>
                <th class="text-right">{{ __('reports.col_qty') }}</th>
                <th class="text-right">{{ __('reports.col_subtotal') }}</th>
                <th class="text-right">{{ __('reports.col_discount') }}</th>
                <th class="text-right">{{ __('reports.col_tax') }}</th>
                <th class="text-right">{{ __('reports.col_total') }}</th>
                <th>{{ __('reports.col_accounting_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->group_label }}</td>
                    <td class="text-right">{{ $row->return_count }}</td>
                    <td class="text-right">{{ decimal($row->total_qty) }}</td>
                    <td class="text-right">{{ currency($row->total_subtotal) }}</td>
                    <td class="text-right">{{ currency($row->total_discount) }}</td>
                    <td class="text-right">{{ currency($row->total_tax) }}</td>
                    <td class="text-right">{{ currency($row->total_amount) }}</td>
                    <td>{{ $row->posted_count }} Posted{{ $row->unposted_count > 0 ? ' / ' . $row->unposted_count . ' Unposted' : '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Qty</td>
            <td class="text-right">{{ decimal($rows->sum('total_qty')) }}</td>
        </tr>
        <tr>
            <td>Total Discount</td>
            <td class="text-right">{{ currency($rows->sum('total_discount')) }}</td>
        </tr>
        <tr>
            <td>Total Tax</td>
            <td class="text-right">{{ currency($rows->sum('total_tax')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Grand Total</td>
            <td class="text-right">{{ currency($rows->sum('total_amount')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
