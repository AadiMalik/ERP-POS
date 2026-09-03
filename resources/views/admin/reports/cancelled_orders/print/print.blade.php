@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Cancelled Orders Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Cancelled Orders Report',
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
                <th>Customer</th>
                <th>Branch</th>
                <th>Order Source</th>
                <th>Status</th>
                <th class="text-right">Amount</th>
                <th>Cancellation Reason</th>
                <th>Cancelled By</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $history = $row->statusHistory->whereIn('to_status', ['cancelled', 'void'])->sortByDesc('date_created')->first();
                @endphp
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->user->name ?? 'Walk-in' }}</td>
                    <td>{{ $row->branch->name ?? '' }}</td>
                    <td>{{ $row->orderSource->name ?? '' }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td>{{ optional($history)->reason ?? '-' }}</td>
                    <td>{{ optional(optional($history)->changedby)->name ?? '-' }}</td>
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
