@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($waste_damage_expiry->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Waste / Damage / Expiry - ' . ($waste_damage_expiry->reference_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $waste_damage_expiry->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $waste_damage_expiry->business,
        'branch' => $waste_damage_expiry->branch,
        'title' => 'Waste / Damage / Expiry',
        'doc_no' => $waste_damage_expiry->reference_no,
        'doc_date' => localDate($waste_damage_expiry->transaction_date),
        'reference' => [
            'Warehouse' => $waste_damage_expiry->warehouse->name ?? 'N/A',
            'Reference' => $waste_damage_expiry->reference ?? 'N/A',
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
                <th>Batch</th>
                <th>Expiry</th>
                <th class="text-right">{{ __('common.quantity') }}</th>
                <th class="text-right">{{ __('common.unit_cost') }}</th>
                <th class="text-right">Value</th>
                <th>Loss Type</th>
                <th>{{ __('common.reason') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($waste_damage_expiry->details as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td>{{ $detail->batch_no ?? '-' }}</td>
                    <td>{{ $detail->expiry_date ? localDate($detail->expiry_date) : '-' }}</td>
                    <td class="text-right">{{ decimal($detail->quantity) }}</td>
                    <td class="text-right">{{ currency($detail->unit_cost) }}</td>
                    <td class="text-right">{{ currency($detail->value) }}</td>
                    <td>{{ \App\Enums\LossType::getOptions()[$detail->loss_type] ?? $detail->loss_type }}</td>
                    <td>{{ $detail->lossReason->name ?? '-' }}</td>
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
            <td>Total Quantity</td>
            <td class="text-right">{{ decimal($waste_damage_expiry->total_quantity) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Value</td>
            <td class="text-right">{{ currency($waste_damage_expiry->total_value) }}</td>
        </tr>
    </table>

    @if (!empty($waste_damage_expiry->notes))
        <div class="print-remarks">
            <strong>{{ __('common.notes') }}:</strong> {{ $waste_damage_expiry->notes }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Created By', 'Approved By'],
        'print_config' => $print_config,
    ])
@endsection
