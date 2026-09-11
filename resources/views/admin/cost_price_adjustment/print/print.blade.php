@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($cost_price_adjustment->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Cost Price Adjustment - ' . ($cost_price_adjustment->reference_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $cost_price_adjustment->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $cost_price_adjustment->business,
        'branch' => $cost_price_adjustment->branch,
        'title' => 'Cost Price Adjustment',
        'doc_no' => $cost_price_adjustment->reference_no,
        'doc_date' => businessDate($cost_price_adjustment->adjustment_date),
        'reference' => [
            'Warehouse' => $cost_price_adjustment->warehouse->name ?? 'N/A',
            'Reference' => $cost_price_adjustment->reference ?? 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('common.product') }}</th>
                <th>{{ __('common.variation') }}</th>
                <th class="text-right">{{ __('cost_price_adjustment.quantity_on_hand') }}</th>
                <th class="text-right">{{ __('cost_price_adjustment.previous_cost_price') }}</th>
                <th class="text-right">{{ __('cost_price_adjustment.new_cost_price') }}</th>
                <th class="text-right">{{ __('cost_price_adjustment.difference_per_unit') }}</th>
                <th class="text-right">{{ __('cost_price_adjustment.total_adjustment_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $cost_price_adjustment->product->name ?? 'N/A' }}</td>
                <td>{{ $cost_price_adjustment->productVariation->name ?? 'N/A' }}</td>
                <td class="text-right">{{ decimal($cost_price_adjustment->quantity_on_hand) }}</td>
                <td class="text-right">{{ currency($cost_price_adjustment->previous_cost_price) }}</td>
                <td class="text-right">{{ currency($cost_price_adjustment->new_cost_price) }}</td>
                <td class="text-right">{{ currency($cost_price_adjustment->difference_per_unit) }}</td>
                <td class="text-right">{{ currency($cost_price_adjustment->total_adjustment_amount) }}</td>
            </tr>
        </tbody>
    </table>

    @if ($cost_price_adjustment->batches->isNotEmpty())
        <table class="print-table">
            <thead>
                <tr>
                    <th>{{ __('common.batch_no') }}</th>
                    <th class="text-right">{{ __('common.quantity') }}</th>
                    <th class="text-right">{{ __('cost_price_adjustment.previous_cost_price') }}</th>
                    <th class="text-right">{{ __('cost_price_adjustment.new_cost_price') }}</th>
                    <th class="text-right">{{ __('cost_price_adjustment.total_adjustment_amount') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cost_price_adjustment->batches as $batch)
                    <tr>
                        <td>{{ $batch->batch_no }}</td>
                        <td class="text-right">{{ decimal($batch->quantity) }}</td>
                        <td class="text-right">{{ currency($batch->previous_batch_avg_price) }}</td>
                        <td class="text-right">{{ currency($batch->new_batch_avg_price) }}</td>
                        <td class="text-right">{{ currency($batch->batch_adjustment_amount) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="print-totals">
        <tr class="grand-total">
            <td>{{ __('cost_price_adjustment.total_adjustment_amount') }}</td>
            <td class="text-right">{{ currency($cost_price_adjustment->total_adjustment_amount) }}</td>
        </tr>
    </table>

    @if (!empty($cost_price_adjustment->reason))
        <div class="print-remarks">
            <strong>{{ __('cost_price_adjustment.reason') }}:</strong> {{ $cost_price_adjustment->reason }}
        </div>
    @endif

    @if (!empty($cost_price_adjustment->notes))
        <div class="print-remarks">
            <strong>{{ __('common.notes') }}:</strong> {{ $cost_price_adjustment->notes }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Created By', 'Approved By'],
        'print_config' => $print_config,
    ])
@endsection
