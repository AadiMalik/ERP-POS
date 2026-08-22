@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($service_sale->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Service Sale - ' . ($service_sale->service_sale_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $service_sale->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $service_sale->business,
        'branch' => $service_sale->branch,
        'title' => 'Service Sale',
        'doc_no' => $service_sale->service_sale_no,
        'doc_date' => localDate($service_sale->service_sale_date),
        'reference' => [
            'Customer' => $service_sale->customer->name ?? 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item / Service</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($service_sale->serviceSaleDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->item_name ?: ($detail->product->name ?? 'N/A') }}</td>
                    <td class="text-right">{{ decimal($detail->quantity) }}</td>
                    <td class="text-right">{{ currency($detail->unit_price) }}</td>
                    <td class="text-right">{{ currency($detail->discount_amount) }}</td>
                    <td class="text-right">{{ currency($detail->tax_amount) }}</td>
                    <td class="text-right">{{ currency($detail->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ currency($service_sale->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">{{ currency($service_sale->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ currency($service_sale->tax_amount) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total</td>
            <td class="text-right">{{ currency($service_sale->total) }}</td>
        </tr>
    </table>

    @if (!empty($service_sale->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $service_sale->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By', 'Delivered By'],
        'print_config' => $print_config,
    ])
@endsection
