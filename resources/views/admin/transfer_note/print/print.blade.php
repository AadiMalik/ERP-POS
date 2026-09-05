@php
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($transfer_note->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Transfer Note - ' . ($transfer_note->transfer_note_no ?? ''))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.status_badge', ['status' => $transfer_note->status, 'print_config' => $print_config])

    @include('admin.partials.print.header', [
        'business' => $transfer_note->business,
        'branch' => $transfer_note->branch,
        'title' => 'Transfer Note',
        'doc_no' => $transfer_note->transfer_note_no,
        'doc_date' => localDate($transfer_note->transfer_note_date),
        'reference' => [
            '{{ __('transfer_notes.source_warehouse') }}' => $transfer_note->sourceWarehouse->name ?? 'N/A',
            '{{ __('transfer_notes.destination_warehouse') }}' => $transfer_note->destinationWarehouse->name ?? 'N/A',
            'Reference' => $transfer_note->reference ?? 'N/A',
            'Status' => ucfirst(str_replace('_', ' ', $transfer_note->status)),
            'Sent By' => $transfer_note->sentby->name ?? 'N/A',
            'Sent Date' => $transfer_note->date_sent ? localDate($transfer_note->date_sent) : 'N/A',
            'Received By' => $transfer_note->receivedby->name ?? 'N/A',
            'Received Date' => $transfer_note->date_received ? localDate($transfer_note->date_received) : 'N/A',
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>{{ __('common.product') }}</th>
                <th>{{ __('common.variation') }}</th>
                <th class="text-right">Transfer Qty</th>
                <th class="text-right">{{ __('common.received_qty') }}</th>
                <th>{{ __('common.unit') }}</th>
                <th class="text-right">{{ __('common.unit_cost') }}</th>
                <th class="text-right">Total Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transfer_note->transferNoteDetails as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->product->name ?? 'N/A' }}</td>
                    <td>{{ $detail->productVariation->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ decimal($detail->transfer_quantity) }}</td>
                    <td class="text-right">{{ decimal($detail->received_quantity) }}</td>
                    <td>{{ $detail->unit->name ?? 'N/A' }}</td>
                    <td class="text-right">{{ currency($detail->unit_cost) }}</td>
                    <td class="text-right">{{ currency($detail->total_value) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No items found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Quantity</td>
            <td class="text-right">{{ decimal($transfer_note->total_quantity) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Value</td>
            <td class="text-right">{{ currency($transfer_note->total_value) }}</td>
        </tr>
    </table>

    @if (!empty($transfer_note->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $transfer_note->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Dispatched By', 'Received By'],
        'print_config' => $print_config,
    ])
@endsection
