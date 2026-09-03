@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Order Correction Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Order Correction Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Order No</th>
                <th>Branch</th>
                <th>Corrected By</th>
                <th>Reason</th>
                <th class="text-right">Previous Total</th>
                <th class="text-right">New Total</th>
                <th class="text-right">Difference</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $old_total = (float) ($row->old_values['total'] ?? 0);
                    $new_total = (float) ($row->new_values['total'] ?? 0);
                @endphp
                <tr>
                    <td>{{ localDateTime($row->date_created) }}</td>
                    <td>{{ optional($row->order)->daily_order_id ?? $row->record_id }}</td>
                    <td>{{ $row->branch->name ?? '' }}</td>
                    <td>{{ $row->causer->name ?? 'System' }}</td>
                    <td>{{ $row->new_values['reason'] ?? '-' }}</td>
                    <td class="text-right">{{ currency(round($old_total, 2)) }}</td>
                    <td class="text-right">{{ currency(round($new_total, 2)) }}</td>
                    <td class="text-right">{{ currency(round($new_total - $old_total, 2)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
