@extends('layouts.print')

@section('title', 'Goods Receipt Note - ' . ($grn->good_receipt_note_no ?? ''))

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $grn->status])

    @include('admin.partials.print.header', [
        'business' => $grn->business,
        'branch' => $grn->branch,
        'title' => 'Goods Receipt Note',
        'doc_no' => $grn->good_receipt_note_no,
        'doc_date' => localDate($grn->good_receipt_note_date),
        'reference' => [
            'Supplier' => $grn->supplier->name ?? 'N/A',
            'Warehouse' => $grn->warehouse->name ?? 'N/A',
            'Purchase No.' => $grn->purchase->purchase_no ?? 'N/A',
        ],
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
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($grn->goodReceiptNoteDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->purchaseDetail->ordered_quantity ?? 0) }}</td>
                    <td class="text-right">{{ decimal($detail->received_quantity) }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ currency($detail->unit_price) }}</td>
                    <td class="text-right">{{ currency($detail->total) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr class="grand-total">
            <td>Total</td>
            <td class="text-right">{{ currency($grn->total) }}</td>
        </tr>
    </table>

    @if (!empty($grn->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $grn->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By', 'Received By'],
    ])
@endsection
