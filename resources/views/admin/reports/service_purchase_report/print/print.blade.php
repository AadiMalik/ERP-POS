@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.service_purchase_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.service_purchase_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_group') }}</th>
                <th class="text-right">Transactions</th>
                <th class="text-right">Purchase Amount</th>
                <th class="text-right">Purchase Return Amount</th>
                <th class="text-right">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->group_label }}</td>
                    <td class="text-right">{{ $row->transaction_count }}</td>
                    <td class="text-right">{{ currency($row->purchase_amount) }}</td>
                    <td class="text-right">{{ currency($row->purchase_return_amount) }}</td>
                    <td class="text-right">{{ currency($row->net_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Purchase Amount</td>
            <td class="text-right">{{ currency($rows->sum('purchase_amount')) }}</td>
        </tr>
        <tr>
            <td>Total Purchase Return Amount</td>
            <td class="text-right">{{ currency($rows->sum('purchase_return_amount')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Net Amount</td>
            <td class="text-right">{{ currency($rows->sum('net_amount')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
