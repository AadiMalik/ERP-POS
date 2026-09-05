@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($order_return->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Order Return - ' . ($order_return->order_return_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $order_return->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $order_return->business,
        'branch' => $order_return->branch,
        'title' => 'Order Return',
        'doc_no' => $order_return->order_return_no,
        'doc_date' => localDate($order_return->order_return_date),
        'reference' => [
            'Customer' => $order_return->customer->name ?? 'N/A',
            'Warehouse' => $order_return->warehouse->name ?? 'N/A',
            'Order No.' => $order_return->order->daily_order_id ?? 'N/A',
            'Refund Method' => $order_return->refundPaymentMethod->name ?? 'Customer Credit',
            'Reason' => $order_return->reason ?? 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('common.product') }}</th>
                <th>{{ __('common.variation') }}</th>
                <th class="text-right">{{ __('common.ordered_qty') }}</th>
                <th class="text-right">Already Returned</th>
                <th class="text-right">Return Qty</th>
                <th>{{ __('common.unit') }}</th>
                <th class="text-right">{{ __('common.unit_price') }}</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">{{ __('common.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order_return->orderReturnDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->ordered_quantity) }}</td>
                    <td class="text-right">{{ decimal($detail->already_returned_quantity) }}</td>
                    <td class="text-right">{{ decimal($detail->return_quantity) }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ currency($detail->unit_price) }}</td>
                    <td class="text-right">{{ currency($detail->discount_amount) }}</td>
                    <td class="text-right">{{ currency($detail->tax_amount) }}</td>
                    <td class="text-right">{{ currency($detail->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>{{ __('common.subtotal') }}</td>
            <td class="text-right">{{ currency($order_return->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">{{ currency($order_return->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ currency($order_return->tax_amount) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total (Refund Amount)</td>
            <td class="text-right">{{ currency($order_return->total) }}</td>
        </tr>
    </table>

    @if (!empty($order_return->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $order_return->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By'],
        'print_config' => $print_config,
    ])
@endsection
