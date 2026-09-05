@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($stock_taking->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Stock Taking - ' . ($stock_taking->stock_taking_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $stock_taking->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $stock_taking->business,
        'branch' => $stock_taking->branch,
        'title' => 'Stock Taking',
        'doc_no' => $stock_taking->stock_taking_no,
        'doc_date' => localDate($stock_taking->stock_taking_date),
        'reference' => [
            'Warehouse' => $stock_taking->warehouse->name ?? 'N/A',
            'Reference' => $stock_taking->reference ?? 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('common.product') }}</th>
                <th>{{ __('common.variation') }}</th>
                <th>{{ __('common.unit') }}</th>
                <th class="text-right">System Qty</th>
                <th class="text-right">Physical Qty</th>
                <th class="text-right">Difference</th>
                <th class="text-right">Diff. Value</th>
                <th>{{ __('common.reason') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($stock_taking->stockTakingDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->system_quantity) }}</td>
                    <td class="text-right">{{ decimal($detail->physical_quantity) }}</td>
                    <td class="text-right">{{ decimal($detail->difference_quantity) }}</td>
                    <td class="text-right">{{ currency($detail->difference_value) }}</td>
                    <td>{{ $detail->reason ?? '-' }}</td>
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
            <td>Total Difference Quantity</td>
            <td class="text-right">{{ decimal($stock_taking->total_difference_quantity) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Difference Value</td>
            <td class="text-right">{{ currency($stock_taking->total_difference_value) }}</td>
        </tr>
    </table>

    @if (!empty($stock_taking->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $stock_taking->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Counted By', 'Approved By'],
        'print_config' => $print_config,
    ])
@endsection
