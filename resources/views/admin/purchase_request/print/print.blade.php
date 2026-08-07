@extends('layouts.print')

@section('title', 'Purchase Request - ' . ($purchase_request->purchase_request_no ?? ''))

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $purchase_request->status])

    @include('admin.partials.print.header', [
        'business' => $purchase_request->business,
        'branch' => $purchase_request->branch,
        'title' => 'Purchase Request',
        'doc_no' => $purchase_request->purchase_request_no,
        'doc_date' => localDate($purchase_request->purchase_request_date),
        'reference' => [
            'Supplier' => $purchase_request->supplier->name ?? 'N/A',
            'Warehouse' => $purchase_request->warehouse->name ?? 'N/A',
            'Expected Date' => !empty($purchase_request->purchase_expected_date) ? localDate($purchase_request->purchase_expected_date) : 'N/A',
        ],
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Product</th>
                <th>Variation</th>
                <th class="text-right">Requested Qty</th>
                <th>Unit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($purchase_request->purchaseRequestDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->requested_quantity) }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (!empty($purchase_request->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $purchase_request->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Requested By', 'Approved By'],
    ])
@endsection
