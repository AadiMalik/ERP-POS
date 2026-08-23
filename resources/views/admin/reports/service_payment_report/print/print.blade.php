@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Service Payment Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Service Payment Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Payment No</th>
                <th>Party</th>
                <th>Reference</th>
                <th>Method</th>
                <th class="text-right">Net Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->payment_date) }}</td>
                    <td>{{ $row->payment_type }}</td>
                    <td>{{ $row->payment_no }}</td>
                    <td>{{ $row->party_name }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $row->payment_method)) }}</td>
                    <td class="text-right">{{ currency($row->net_amount) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Receipts</td>
            <td class="text-right">{{ currency($rows->where('payment_type', 'Receipt')->sum('net_amount')) }}</td>
        </tr>
        <tr>
            <td>Total Payments</td>
            <td class="text-right">{{ currency($rows->where('payment_type', 'Payment')->sum('net_amount')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Net Cash Flow</td>
            <td class="text-right">
                {{ currency($rows->where('payment_type', 'Receipt')->sum('net_amount') - $rows->where('payment_type', 'Payment')->sum('net_amount')) }}
            </td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
