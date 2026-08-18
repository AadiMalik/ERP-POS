{{--
    Business-level thermal receipt for POS/order sales.
    Expects: $order (App\Models\Order, with details/payments/relations loaded),
             $thermal_config (App\Support\Print\ThermalPrintConfig)
--}}
@php
    $business = $order->business;
    $branch = $order->branch;

    $branch_name = optional($branch)->name ?? optional($business)->name;
    $branch_logo = optional($branch)->logo;
    $business_logo = optional($business)->logo;
    $email = optional($branch)->email ?? optional($business)->email;
    $phone = optional($branch)->phone ?? optional($business)->phone;
    $address = collect([
        optional($branch)->address ?? optional($business)->address,
        optional($branch)->city ?? optional($business)->city,
        optional($branch)->state ?? optional($business)->state,
        optional($branch)->country ?? optional($business)->country,
    ])->filter()->implode(', ');
    $ntn = optional(optional($business)->fbrSetting)->fbr_ntn;

    $due = max(($order->total ?? 0) - ($order->paid_amount ?? 0), 0);
    if ($due <= 0) {
        $payment_status = \App\Enums\Status::PAID;
    } elseif (($order->paid_amount ?? 0) > 0) {
        $payment_status = \App\Enums\Status::PARTIALLY_PAID;
    } else {
        $payment_status = \App\Enums\Status::UNPAID;
    }
    $payment_status_label = ucwords(str_replace('_', ' ', $payment_status));

    $item_columns = [];
    if ($thermal_config->isVisible('quantity')) {
        $item_columns[] = 'quantity';
    }
    if ($thermal_config->isVisible('unit')) {
        $item_columns[] = 'unit';
    }
    if ($thermal_config->isVisible('unit_price')) {
        $item_columns[] = 'unit_price';
    }
    if ($thermal_config->isVisible('line_total')) {
        $item_columns[] = 'line_total';
    }
@endphp
@extends('layouts.print')

@section('title', 'Receipt - ' . ($order->daily_order_id ?? ''))

@section('page_class', 'thermal-page')

@section('css')
    <link rel="stylesheet" href="{{ asset('public/assets/css/print-thermal.css') }}">
    <style>
        @page {
            size: {{ $thermal_config->paperWidthMm() }}mm auto;
            margin: 0 2mm;
        }

        .print-page.thermal-page {
            max-width: {{ $thermal_config->paperWidthMm() }}mm;
        }
    </style>
@endsection

@section('content')
    <div class="thermal-receipt">

        @if ($thermal_config->isVisible('branch_logo') || $thermal_config->isVisible('branch_name') || $thermal_config->isVisible('email') || $thermal_config->isVisible('phone') || $thermal_config->isVisible('address') || $thermal_config->isVisible('business_ntn'))
            <div class="tr-center">
                @if ($thermal_config->isVisible('branch_logo') && ($branch_logo || $business_logo))
                    <img class="tr-logo"
                        src="{{ $branch_logo ? asset('public/uploads/branch/' . $branch_logo) : asset('public/uploads/business/' . $business_logo) }}"
                        alt="Logo">
                @endif

                @if ($thermal_config->isVisible('branch_name') && $branch_name)
                    <p class="tr-name">{{ $branch_name }}</p>
                @endif

                @if ($thermal_config->isVisible('address') && $address)
                    <p class="tr-meta-line">{{ $address }}</p>
                @endif

                @if ($thermal_config->isVisible('phone') && $phone)
                    <p class="tr-meta-line">Tel: {{ $phone }}</p>
                @endif

                @if ($thermal_config->isVisible('email') && $email)
                    <p class="tr-meta-line">{{ $email }}</p>
                @endif

                @if ($thermal_config->isVisible('business_ntn') && $ntn)
                    <p class="tr-meta-line">NTN: {{ $ntn }}</p>
                @endif
            </div>
            <hr class="tr-divider">
        @endif

        @if ($thermal_config->isVisible('customer_name') || $thermal_config->isVisible('order_type') || $thermal_config->isVisible('order_no') || $thermal_config->isVisible('date_time') || $thermal_config->isVisible('order_source') || $thermal_config->isVisible('order_taker_name'))
            <div class="tr-meta">
                @if ($thermal_config->isVisible('order_no'))
                    <div class="tr-row">
                        <span class="tr-label">Order No:</span>
                        <span class="tr-value">{{ $order->daily_order_id ?? 'N/A' }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('date_time'))
                    <div class="tr-row">
                        <span class="tr-label">Date:</span>
                        <span class="tr-value">{{ localDateTime($order->sale_date ?? $order->order_date) }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('customer_name') && !empty($order->user->name))
                    <div class="tr-row">
                        <span class="tr-label">Customer:</span>
                        <span class="tr-value">{{ $order->user->name }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('order_type') && !empty($order->orderType->name))
                    <div class="tr-row">
                        <span class="tr-label">Order Type:</span>
                        <span class="tr-value">{{ $order->orderType->name }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('order_source') && !empty($order->orderSource->name))
                    <div class="tr-row">
                        <span class="tr-label">Source:</span>
                        <span class="tr-value">{{ $order->orderSource->name }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('order_taker_name') && !empty($order->cashier->name))
                    <div class="tr-row">
                        <span class="tr-label">Served by:</span>
                        <span class="tr-value">{{ $order->cashier->name }}</span>
                    </div>
                @endif
            </div>
            <hr class="tr-divider">
        @endif

        {{-- Always shown regardless of field_config - a reprint must always be
             distinguishable from the original print by its own timestamp. --}}
        <div class="tr-meta">
            <div class="tr-row">
                <span class="tr-label">Printed On:</span>
                <span class="tr-value">{{ localDateTime($printed_at) }}</span>
            </div>
        </div>
        <hr class="tr-divider">

        <table class="tr-items">
            <thead>
                <tr>
                    <th>Item</th>
                    @if (in_array('quantity', $item_columns))
                        <th class="text-right">Qty</th>
                    @endif
                    @if (in_array('unit', $item_columns))
                        <th>Unit</th>
                    @endif
                    @if (in_array('unit_price', $item_columns))
                        <th class="text-right">Price</th>
                    @endif
                    @if (in_array('line_total', $item_columns))
                        <th class="text-right">Total</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($order->details as $detail)
                    <tr>
                        <td class="tr-item-name">{{ $detail->product->name ?? 'N/A' }}</td>
                        @if (in_array('quantity', $item_columns))
                            <td class="text-right">{{ decimal($detail->quantity) }}</td>
                        @endif
                        @if (in_array('unit', $item_columns))
                            <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                        @endif
                        @if (in_array('unit_price', $item_columns))
                            <td class="text-right">{{ currency($detail->unit_price) }}</td>
                        @endif
                        @if (in_array('line_total', $item_columns))
                            <td class="text-right">{{ currency($detail->total) }}</td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($item_columns) + 1 }}" class="tr-center">No items</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <hr class="tr-divider">

        @if ($thermal_config->isVisible('subtotal') || $thermal_config->isVisible('discount') || $thermal_config->isVisible('tax') || $thermal_config->isVisible('voucher') || $thermal_config->isVisible('total'))
            <div class="tr-totals">
                @if ($thermal_config->isVisible('subtotal'))
                    <div class="tr-row">
                        <span class="tr-label">Subtotal</span>
                        <span class="tr-value">{{ currency($order->subtotal) }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('discount') && (float) $order->discount_amount > 0)
                    <div class="tr-row">
                        <span class="tr-label">Discount ({{ decimal($order->discount) }}%)</span>
                        <span class="tr-value">-{{ currency($order->discount_amount) }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('tax') && (float) $order->tax_amount > 0)
                    <div class="tr-row">
                        <span class="tr-label">Tax ({{ decimal($order->tax) }}%)</span>
                        <span class="tr-value">{{ currency($order->tax_amount) }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('voucher') && !empty($order->voucher) && (float) $order->voucher_discount_amount > 0)
                    <div class="tr-row">
                        <span class="tr-label">Voucher ({{ $order->voucher->code }})</span>
                        <span class="tr-value">-{{ currency($order->voucher_discount_amount) }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('total'))
                    <div class="tr-row tr-grand-total">
                        <span class="tr-label">TOTAL</span>
                        <span class="tr-value">{{ currency($order->total) }}</span>
                    </div>
                @endif
            </div>
        @endif

        @if ($thermal_config->isVisible('paid_amount') || $thermal_config->isVisible('due_amount') || $thermal_config->isVisible('payment_status'))
            <hr class="tr-divider">
            <div class="tr-payment">
                @if ($thermal_config->isVisible('paid_amount'))
                    <div class="tr-row">
                        <span class="tr-label">Paid</span>
                        <span class="tr-value">{{ currency($order->paid_amount) }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('due_amount') && $due > 0)
                    <div class="tr-row">
                        <span class="tr-label">Due</span>
                        <span class="tr-value">{{ currency($due) }}</span>
                    </div>
                @endif

                @if ($thermal_config->isVisible('payment_status'))
                    <div class="tr-row">
                        <span class="tr-label">Status</span>
                        <span class="tr-value">{{ $payment_status_label }}</span>
                    </div>
                @endif
            </div>
        @endif

        @if ($thermal_config->isVisible('thank_you_note') || $thermal_config->isVisible('qr_code') || $thermal_config->isVisible('powered_by_smart_mart'))
            <hr class="tr-divider">
            <div class="tr-footer">
                @if ($thermal_config->isVisible('thank_you_note') && $thermal_config->footerMeta('thank_you_note'))
                    <p class="tr-thank-you">{{ $thermal_config->footerMeta('thank_you_note') }}</p>
                @endif

                @if ($thermal_config->isVisible('qr_code'))
                    @php
                        $qr_source = $thermal_config->footerMeta('qr_data_source', 'order_no');
                        $qr_payload = match ($qr_source) {
                            'order_url' => route('order.print', $order->order_id),
                            'custom' => (string) $thermal_config->footerMeta('qr_custom_text'),
                            default => (string) ($order->daily_order_id ?? $order->order_id),
                        };
                    @endphp
                    @if (!empty($qr_payload))
                        <div class="tr-qr">
                            {!! app(\App\Services\Concrete\QrCodeGeneratorService::class)->renderSvg($qr_payload, 150) !!}
                        </div>
                    @endif
                @endif

                @if ($thermal_config->isVisible('powered_by_smart_mart'))
                    <p class="tr-powered-by">Powered by Smart Mart ERP</p>
                @endif
            </div>
        @endif
    </div>
@endsection
