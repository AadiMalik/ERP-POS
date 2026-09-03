@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Offline Orders Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Offline Orders Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Order No</th>
                <th>Date</th>
                <th>Branch</th>
                <th>Device</th>
                <th>Customer</th>
                <th>Status</th>
                <th class="text-right">Total</th>
                <th>Offline Local ID</th>
                <th>Last Sync</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->branch->name ?? '' }}</td>
                    <td>{{ $row->posDevice->name ?? '-' }}</td>
                    <td>{{ $row->user->name ?? 'Walk-in' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td>{{ $row->offline_local_id ?? '-' }}</td>
                    <td>{{ optional(optional($row->posDevice)->last_sync_at)->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
