@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($order->business_id, 'pos_receipt');
@endphp
@extends('layouts.print')

@section('title', 'POS Order - ' . ($order->daily_order_id ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $order->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $order->business,
        'branch' => $order->branch,
        'title' => 'POS Sale Receipt',
        'doc_no' => $order->daily_order_id,
        'doc_date' => $order->sale_date ? localDate($order->sale_date) : localDate($order->order_date),
        'reference' => [
            'Register' => $order->register->name ?? 'N/A',
            'Cashier' => $order->cashier->name ?? 'N/A',
            'Customer' => $order->user->name ?? 'N/A',
            'Order Type' => $order->orderType->name ?? 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('common.product') }}</th>
                <th>{{ __('common.variation') }}</th>
                <th class="text-right">{{ __('common.qty') }}</th>
                <th>{{ __('common.unit') }}</th>
                <th class="text-right">{{ __('common.unit_price') }}</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">{{ __('common.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->quantity) }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ currency($detail->unit_price) }}</td>
                    <td class="text-right">{{ currency($detail->discount_amount) }}</td>
                    <td class="text-right">{{ currency($detail->tax_amount) }}</td>
                    <td class="text-right">{{ currency($detail->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>{{ __('common.subtotal') }}</td>
            <td class="text-right">{{ currency($order->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">{{ currency($order->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ currency($order->tax_amount) }}</td>
        </tr>
        <tr class="grand-total">
            <td>{{ __('common.total') }}</td>
            <td class="text-right">{{ currency($order->total) }}</td>
        </tr>
    </table>

    {{-- POS orders can be settled across multiple payment methods in a
         single sale (cash + card split, etc.) - this breakdown has no
         equivalent in the single-payer Purchase print view. --}}
    <table class="print-table" style="margin-top: 10px;">
        <thead>
            <tr>
                <th>Payment Method</th>
                <th>{{ __('waste_damage_expiry.reference_no') }}</th>
                <th class="text-right">{{ __('common.amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($order->payments as $payment)
                <tr>
                    <td>{{ $payment->paymentMethod->name ?? 'N/A' }}</td>
                    <td>{{ $payment->reference_no ?? '-' }}</td>
                    <td class="text-right">{{ currency($payment->amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No payments recorded</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (!empty($order->notes))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $order->notes }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Cashier', 'Customer'],
        'print_config' => $print_config,
    ])
@endsection
