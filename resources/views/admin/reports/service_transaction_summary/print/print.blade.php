@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.service_transaction_summary'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.service_transaction_summary'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_group') }}</th>
                <th class="text-right">Sale</th>
                <th class="text-right">Sale Return</th>
                <th class="text-right">Net Sale</th>
                <th class="text-right">Purchase</th>
                <th class="text-right">Purchase Return</th>
                <th class="text-right">Net Purchase</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->group_label }}</td>
                    <td class="text-right">{{ currency($row->sale_amount) }}</td>
                    <td class="text-right">{{ currency($row->sale_return_amount) }}</td>
                    <td class="text-right">{{ currency($row->net_sale_amount) }}</td>
                    <td class="text-right">{{ currency($row->purchase_amount) }}</td>
                    <td class="text-right">{{ currency($row->purchase_return_amount) }}</td>
                    <td class="text-right">{{ currency($row->net_purchase_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Sale</td>
            <td class="text-right">{{ currency($rows->sum('sale_amount')) }}</td>
        </tr>
        <tr>
            <td>Total Sale Return</td>
            <td class="text-right">{{ currency($rows->sum('sale_return_amount')) }}</td>
        </tr>
        <tr>
            <td>Net Sale</td>
            <td class="text-right">{{ currency($rows->sum('net_sale_amount')) }}</td>
        </tr>
        <tr>
            <td>Total Purchase</td>
            <td class="text-right">{{ currency($rows->sum('purchase_amount')) }}</td>
        </tr>
        <tr>
            <td>Total Purchase Return</td>
            <td class="text-right">{{ currency($rows->sum('purchase_return_amount')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Net Purchase</td>
            <td class="text-right">{{ currency($rows->sum('net_purchase_amount')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
