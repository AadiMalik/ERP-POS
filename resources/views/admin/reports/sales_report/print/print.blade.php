@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.sales_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.sales_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_order_no') }}</th>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_customer') }}</th>
                <th>{{ __('reports.col_warehouse') }}</th>
                <th class="text-right">{{ __('reports.col_subtotal') }}</th>
                <th class="text-right">{{ __('reports.col_discount') }}</th>
                <th class="text-right">Voucher</th>
                <th class="text-right">{{ __('reports.col_tax') }}</th>
                <th class="text-right">{{ __('reports.col_total') }}</th>
                <th class="text-right">{{ __('reports.col_paid') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ optional($row->user)->name ?? 'Walk-in' }}</td>
                    <td>{{ optional($row->warehouse)->name ?? '' }}</td>
                    <td class="text-right">{{ currency($row->subtotal) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->voucher_discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td class="text-right">{{ currency($row->paid_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Orders</td>
            <td class="text-right">{{ $summary['order_count'] }}</td>
        </tr>
        <tr>
            <td>Order Total (Subtotal before discount/tax)</td>
            <td class="text-right">{{ currency($summary['order_subtotal']) }}</td>
        </tr>
        <tr>
            <td>Posted Sales Revenue (Ledger)</td>
            <td class="text-right">{{ currency($summary['ledger_revenue']) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Variance</td>
            <td class="text-right">{{ currency($summary['variance']) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
