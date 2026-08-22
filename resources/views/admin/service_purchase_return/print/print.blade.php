@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($service_purchase_return->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Service Purchase Return - ' . ($service_purchase_return->service_purchase_return_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $service_purchase_return->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $service_purchase_return->business,
        'branch' => $service_purchase_return->branch,
        'title' => 'Service Purchase Return',
        'doc_no' => $service_purchase_return->service_purchase_return_no,
        'doc_date' => localDate($service_purchase_return->service_purchase_return_date),
        'reference' => [
            'Supplier' => $service_purchase_return->supplier->name ?? 'N/A',
            'Service Purchase No.' => $service_purchase_return->servicePurchase->service_purchase_no ?? 'N/A',
            'Reason' => $service_purchase_return->reason ?: 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item / Service</th>
                <th class="text-right">Return Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($service_purchase_return->servicePurchaseReturnDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->item_name ?: ($detail->product->name ?? 'N/A') }}</td>
                    <td class="text-right">{{ decimal($detail->return_quantity) }}</td>
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
            <td class="text-right">{{ currency($service_purchase_return->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">{{ currency($service_purchase_return->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ currency($service_purchase_return->tax_amount) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total</td>
            <td class="text-right">{{ currency($service_purchase_return->total) }}</td>
        </tr>
    </table>

    @if (!empty($service_purchase_return->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $service_purchase_return->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By'],
        'print_config' => $print_config,
    ])
@endsection
