{{--
    Thermal-formatted Sales Summary for the POS Order History "Sales Summary"
    panel - printed from inside the POS interface, never the Admin Panel.
    Expects: $summary (array from OrderService::getHistorySummary()),
             $thermal_config (App\Support\Print\ThermalPrintConfig),
             $business (App\Models\Business|null),
             $filters (array - the raw query filters the summary was built from),
             $printed_at (Carbon)
--}}
@extends('layouts.print')

@section('title', 'Sales Summary')

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
            <p class="tr-meta-line">Sales Summary</p>
        </div>
        <hr class="tr-divider">

        <div class="tr-meta">
            <div class="tr-row">
                <span class="tr-label">From:</span>
                <span class="tr-value">{{ !empty($filters['sale_date_start']) ? localDate($filters['sale_date_start']) : 'All time' }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">To:</span>
                <span class="tr-value">{{ !empty($filters['sale_date_end']) ? localDate($filters['sale_date_end']) : 'Today' }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Printed On:</span>
                <span class="tr-value">{{ localDateTime($printed_at) }}</span>
            </div>
        </div>
        <hr class="tr-divider">

        <div class="tr-totals">
            <div class="tr-row">
                <span class="tr-label">Total Orders</span>
                <span class="tr-value">{{ $summary['total_orders'] ?? 0 }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Total Sales</span>
                <span class="tr-value">{{ currency($summary['total_sales'] ?? 0) }}</span>
            </div>
            <div class="tr-row">
                <span class="tr-label">Total Paid</span>
                <span class="tr-value">{{ currency($summary['total_paid'] ?? 0) }}</span>
            </div>
            <div class="tr-row tr-grand-total">
                <span class="tr-label">Total Due</span>
                <span class="tr-value">{{ currency($summary['total_due'] ?? 0) }}</span>
            </div>
        </div>

        @if (!empty($summary['by_status']))
            <hr class="tr-divider">
            <p class="tr-meta-line" style="font-weight:700;">By Order Status</p>
            <div class="tr-totals">
                @foreach ($summary['by_status'] as $status => $count)
                    <div class="tr-row">
                        <span class="tr-label">{{ ucfirst($status) }}</span>
                        <span class="tr-value">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if (!empty($summary['by_payment_method']))
            <hr class="tr-divider">
            <p class="tr-meta-line" style="font-weight:700;">By Payment Method</p>
            <div class="tr-totals">
                @foreach ($summary['by_payment_method'] as $method => $amount)
                    <div class="tr-row">
                        <span class="tr-label">{{ $method }}</span>
                        <span class="tr-value">{{ currency($amount) }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        <hr class="tr-divider">
        <div class="tr-footer">
            <p class="tr-powered-by">Powered by Smart Mart ERP</p>
        </div>
    </div>
@endsection
