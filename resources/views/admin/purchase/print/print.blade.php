@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($purchase->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Purchase - ' . ($purchase->purchase_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $purchase->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $purchase->business,
        'branch' => $purchase->branch,
        'title' => 'Purchase Order',
        'doc_no' => $purchase->purchase_no,
        'doc_date' => localDate($purchase->purchase_date),
        'reference' => [
            'Supplier' => $purchase->supplier->name ?? 'N/A',
            'Warehouse' => $purchase->warehouse->name ?? 'N/A',
            'Purchase Request No.' => $purchase->purchaseRequest->purchase_request_no ?? 'N/A',
            'Expected Delivery Date' => !empty($purchase->expected_delivery_date) ? localDate($purchase->expected_delivery_date) : 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Variation</th>
                <th class="text-right">Ordered Qty</th>
                <th class="text-right">Received Qty</th>
                <th>Unit</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchase->purchaseDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->ordered_quantity) }}</td>
                    <td class="text-right">{{ decimal($detail->received_quantity) }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ currency($detail->unit_price) }}</td>
                    <td class="text-right">{{ currency($detail->discount_amount) }}</td>
                    <td class="text-right">{{ currency($detail->tax_amount) }}</td>
                    <td class="text-right">{{ currency($detail->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">{{ currency($purchase->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">{{ currency($purchase->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ currency($purchase->tax_amount) }}</td>
        </tr>
        <tr>
            <td>Shipping Charge</td>
            <td class="text-right">{{ currency($purchase->shipping_charge) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total</td>
            <td class="text-right">{{ currency($purchase->total) }}</td>
        </tr>
    </table>

    @if (!empty($purchase->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $purchase->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By', 'Received By'],
        'print_config' => $print_config,
    ])
@endsection
