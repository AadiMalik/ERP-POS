@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Order Detail Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Order Detail Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Order No</th>
                <th>Date/Time</th>
                <th>Customer</th>
                <th>Branch</th>
                <th>Order Source</th>
                <th>Status</th>
                <th>Payment Status</th>
                <th>Product</th>
                <th>Variation</th>
                <th>SKU</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Delivery</th>
                <th class="text-right">Final Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $due = max(($row->order_total ?? 0) - ($row->order_paid_amount ?? 0), 0);
                    $payment_status = $due <= 0 ? 'paid' : (($row->order_paid_amount ?? 0) > 0 ? 'partially_paid' : 'unpaid');
                @endphp
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->customer_name ?? 'Walk-in' }}</td>
                    <td>{{ $row->branch_name ?? '' }}</td>
                    <td>{{ $row->order_source_name ?? '' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->order_status)) }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment_status)) }}</td>
                    <td>{{ $row->product_name ?? '' }}</td>
                    <td>{{ $row->variation_name ?? '' }}</td>
                    <td>{{ $row->sku ?? '' }}</td>
                    <td class="text-right">{{ decimal($row->quantity) }}</td>
                    <td class="text-right">{{ currency($row->unit_price) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency(0) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="16" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
