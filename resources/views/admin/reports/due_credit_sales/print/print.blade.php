@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Due / Credit Sales Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Due / Credit Sales Report',
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
                <th class="text-right">Total Amount</th>
                <th class="text-right">Paid Amount</th>
                <th class="text-right">Due Amount</th>
                <th>Payment Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $due = max(($row->total ?? 0) - ($row->paid_amount ?? 0), 0);
                    $payment_status = $due <= 0 ? 'paid' : (($row->paid_amount ?? 0) > 0 ? 'partially_paid' : 'unpaid');
                @endphp
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->user->name ?? 'Walk-in' }}</td>
                    <td>{{ $row->branch->name ?? '' }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td class="text-right">{{ currency($row->paid_amount) }}</td>
                    <td class="text-right">{{ currency($due) }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $payment_status)) }}</td>
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
