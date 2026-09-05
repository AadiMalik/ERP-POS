@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($purchase_return->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Purchase Return - ' . ($purchase_return->purchase_return_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $purchase_return->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $purchase_return->business,
        'branch' => $purchase_return->branch,
        'title' => 'Purchase Return',
        'doc_no' => $purchase_return->purchase_return_no,
        'doc_date' => localDate($purchase_return->purchase_return_date),
        'reference' => [
            'Supplier' => $purchase_return->supplier->name ?? 'N/A',
            'Warehouse' => $purchase_return->warehouse->name ?? 'N/A',
            'Return Type' => $purchase_return->return_type === 'grn' ? 'GRN' : 'Direct Purchase',
            'Source No.' => $purchase_return->return_type === 'grn'
                ? ($purchase_return->goodReceiptNote->good_receipt_note_no ?? 'N/A')
                : ($purchase_return->purchase->purchase_no ?? 'N/A'),
            'Reason' => $purchase_return->reason ?? 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('common.product') }}</th>
                <th>{{ __('common.variation') }}</th>
                <th class="text-right">{{ __('common.received_qty') }}</th>
                <th class="text-right">Already Returned</th>
                <th class="text-right">Return Qty</th>
                <th>{{ __('common.unit') }}</th>
                <th class="text-right">{{ __('common.unit_cost') }}</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">{{ __('common.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchase_return->purchaseReturnDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->received_quantity) }}</td>
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
            <td class="text-right">{{ currency($purchase_return->subtotal) }}</td>
        </tr>
        <tr>
            <td>Discount</td>
            <td class="text-right">{{ currency($purchase_return->discount_amount) }}</td>
        </tr>
        <tr>
            <td>Tax</td>
            <td class="text-right">{{ currency($purchase_return->tax_amount) }}</td>
        </tr>
        <tr class="grand-total">
            <td>{{ __('common.total') }}</td>
            <td class="text-right">{{ currency($purchase_return->total) }}</td>
        </tr>
    </table>

    @if (!empty($purchase_return->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $purchase_return->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By'],
        'print_config' => $print_config,
    ])
@endsection
