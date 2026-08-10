@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($quotation->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Quotation - ' . ($quotation->purchase_request_quotation_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $quotation->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $quotation->business,
        'branch' => $quotation->branch,
        'title' => 'Quotation',
        'doc_no' => $quotation->purchase_request_quotation_no,
        'doc_date' => localDate($quotation->sent_date),
        'reference' => [
            'Supplier' => $quotation->supplier->name ?? 'N/A',
            'Purchase Request No.' => $quotation->purchaseRequest->purchase_request_no ?? 'N/A',
            'Received Date' => !empty($quotation->received_date) ? localDate($quotation->received_date) : 'N/A',
            'Vendor Reference No.' => $quotation->vendor_reference_no ?? 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Variation</th>
                <th class="text-right">Requested Qty</th>
                <th>Unit</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($quotation->purchaseRequestQuotationDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->requested_quantity) }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ currency($detail->unit_price) }}</td>
                    <td class="text-right">{{ currency($detail->subtotal) }}</td>
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
            <td class="text-right">{{ currency($quotation->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">{{ currency($quotation->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ currency($quotation->tax_amount) }}</td>
        </tr>
        @if (!empty($quotation->other_charge))
            <tr>
                <td>Other Charges</td>
                <td class="text-right">{{ currency($quotation->other_charge) }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>Total</td>
            <td class="text-right">{{ currency($quotation->total) }}</td>
        </tr>
    </table>

    @if (!empty($quotation->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $quotation->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By'],
        'print_config' => $print_config,
    ])
@endsection
