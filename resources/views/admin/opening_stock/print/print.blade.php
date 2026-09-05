@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($opening_stock->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Opening Stock - ' . ($opening_stock->opening_stock_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $opening_stock->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $opening_stock->business,
        'branch' => $opening_stock->branch,
        'title' => 'Opening Stock',
        'doc_no' => $opening_stock->opening_stock_no,
        'doc_date' => localDate($opening_stock->opening_stock_date),
        'reference' => [
            'Warehouse' => $opening_stock->warehouse->name ?? 'N/A',
            'Reference' => $opening_stock->reference ?? 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('common.product') }}</th>
                <th>{{ __('common.variation') }}</th>
                <th class="text-right">{{ __('common.quantity') }}</th>
                <th>{{ __('common.unit') }}</th>
                <th class="text-right">{{ __('common.unit_cost') }}</th>
                <th>{{ __('common.batch_no') }}</th>
                <th>{{ __('common.expiry_date') }}</th>
                <th class="text-right">Total Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($opening_stock->openingStockDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->quantity) }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ currency($detail->unit_cost) }}</td>
                    <td>{{ $detail->batch_no ?? '-' }}</td>
                    <td>{{ !empty($detail->expiry_date) ? localDate($detail->expiry_date) : '-' }}</td>
                    <td class="text-right">{{ currency($detail->total_value) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Quantity</td>
            <td class="text-right">{{ decimal($opening_stock->total_quantity) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Value</td>
            <td class="text-right">{{ currency($opening_stock->total_value) }}</td>
        </tr>
    </table>

    @if (!empty($opening_stock->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $opening_stock->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Approved By'],
        'print_config' => $print_config,
    ])
@endsection
