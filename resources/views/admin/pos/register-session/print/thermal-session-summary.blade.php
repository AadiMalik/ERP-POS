{{--
    Thermal-formatted Register Session Summary - printed from the POS
    Reports panel's session detail view. Mirrors
    resources/views/admin/order/print/thermal-sales-summary.blade.php's
    structure/classes.
    Expects: $session (App\Models\PosRegisterSession),
             $summary (array from PosRegisterSessionService::getSummary()),
             $thermal_config (App\Support\Print\ThermalPrintConfig),
             $business (App\Models\Business|null),
             $printed_at (Carbon)
--}}
@extends('layouts.print')

@section('title', 'Session Summary')

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
        <div class="tr-center">
            @if ($business && $business->name)
                <p class="tr-name">{{ $business->name }}</p>
            @endif
            <p class="tr-meta-line">Register Session Summary</p>
        </div>
        <hr class="tr-divider">

        <div class="tr-meta">
            <div class="tr-row">
                <span class="tr-label">Session:</span>
                <span class="tr-value">{{ $session->pos_register_session_id }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Opened:</span>
                <span class="tr-value">{{ $session->opening_datetime ? localDateTime($session->opening_datetime) : '-' }}</span>
            </div>
            @if ($session->closing_datetime)
                <div class="tr-row">
                    <span class="tr-label">Closed:</span>
                    <span class="tr-value">{{ localDateTime($session->closing_datetime) }}</span>
                </div>
            @endif
            <div class="tr-row">
                <span class="tr-label">Printed On:</span>
                <span class="tr-value">{{ localDateTime($printed_at) }}</span>
            </div>
        </div>
        <hr class="tr-divider">

        <div class="tr-totals">
            <div class="tr-row tr-grand-total">
                <span class="tr-label">Total ({{ $summary['total_orders'] ?? 0 }})</span>
                <span class="tr-value">{{ currency($summary['total_sales_amount'] ?? 0) }}</span>
            </div>
        </div>

        <hr class="tr-divider">
        <p class="tr-meta-line" style="font-weight:700;">Payments</p>
        <div class="tr-totals">
            @foreach ($summary['payment_method_totals'] ?? [] as $row)
                <div class="tr-row">
                    <span class="tr-label">{{ $row['name'] }} ({{ $row['order_count'] ?? 0 }})</span>
                    <span class="tr-value">{{ currency($row['total'] ?? 0) }}</span>
                </div>
            @endforeach
            @if (!empty($summary['multi_payment_order_count']))
                <div class="tr-row">
                    <span class="tr-label">Multi ({{ $summary['multi_payment_order_count'] }})</span>
                    <span class="tr-value">{{ currency($summary['multi_payment_amount'] ?? 0) }}</span>
                </div>
            @endif
        </div>

        @if (!empty($summary['order_source_totals']))
            <hr class="tr-divider">
            <p class="tr-meta-line" style="font-weight:700;">Order Type</p>
            <div class="tr-totals">
                @foreach ($summary['order_source_totals'] as $row)
                    <div class="tr-row">
                        <span class="tr-label">{{ $row->name }} ({{ $row->order_count ?? 0 }})</span>
                        <span class="tr-value">{{ currency($row->total ?? 0) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <hr class="tr-divider">
        <div class="tr-totals">
            <div class="tr-row">
                <span class="tr-label">Discount ({{ $summary['discount_order_count'] ?? 0 }})</span>
                <span class="tr-value">{{ currency($summary['total_discount'] ?? 0) }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Tax ({{ $summary['tax_order_count'] ?? 0 }})</span>
                <span class="tr-value">{{ currency($summary['total_tax'] ?? 0) }}</span>
            </div>
        </div>

        <hr class="tr-divider">
        <div class="tr-totals">
            <div class="tr-row">
                <span class="tr-label">Opening Amount</span>
                <span class="tr-value">{{ currency($summary['opening_cash'] ?? 0) }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Cash Refunds (-)</span>
                <span class="tr-value">{{ currency($summary['cash_refunds'] ?? 0) }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Cash In (+)</span>
                <span class="tr-value">{{ currency($summary['cash_movements_in'] ?? 0) }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Cash Out (-)</span>
                <span class="tr-value">{{ currency($summary['cash_movements_out'] ?? 0) }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Expenses (-)</span>
                <span class="tr-value">{{ currency($summary['total_expenses'] ?? 0) }}</span>
            </div>
            <div class="tr-row tr-grand-total">
                <span class="tr-label">Cash Amount</span>
                <span class="tr-value">{{ currency($summary['expected_cash'] ?? 0) }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Actual</span>
                <span class="tr-value">{{ $summary['actual_cash'] !== null ? currency($summary['actual_cash']) : '-' }}</span>
            </div>
        </div>

        <hr class="tr-divider">
        <div class="tr-footer">
            <p class="tr-powered-by">Powered by Dukanaz</p>
        </div>
    </div>
@endsection
