@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($service_purchase->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Service Purchase - ' . ($service_purchase->service_purchase_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $service_purchase->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $service_purchase->business,
        'branch' => $service_purchase->branch,
        'title' => 'Service Purchase',
        'doc_no' => $service_purchase->service_purchase_no,
        'doc_date' => localDate($service_purchase->service_purchase_date),
        'reference' => [
            'Supplier' => $service_purchase->supplier->name ?? 'N/A',
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
            @forelse ($service_purchase->servicePurchaseDetails as $index => $detail)
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
            <td class="text-right">{{ currency($service_purchase->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">{{ currency($service_purchase->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ currency($service_purchase->tax_amount) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total</td>
            <td class="text-right">{{ currency($service_purchase->total) }}</td>
        </tr>
    </table>

    @if (!empty($service_purchase->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $service_purchase->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By', 'Received By'],
        'print_config' => $print_config,
    ])
@endsection
