@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.customer_payment_history'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.customer_payment_history'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_payment_date') }}</th>
                <th>{{ __('reports.col_payment_no') }}</th>
                <th>{{ __('reports.col_customer') }}</th>
                <th>{{ __('reports.col_method') }}</th>
                <th>{{ __('reports.col_ref_order') }}</th>
                <th>{{ __('reports.col_bank_cash_account') }}</th>
                <th class="text-right">{{ __('reports.col_tax') }}</th>
                <th class="text-right">{{ __('reports.col_discount') }}</th>
                <th class="text-right">Net Payment</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->payment_date) }}</td>
                    <td>{{ $row->payment_no }}</td>
                    <td>{{ $row->user->name ?? '' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $row->payment_method)) }}</td>
                    <td>{{ $row->order_id ? ($row->order->daily_order_id ?? $row->order_id) : 'On Account' }}</td>
                    <td>{{ $row->paymentAccount->name ?? '' }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->net_amount) }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No payments found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="6"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('tax_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('discount_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('net_amount')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
