@php
    $business = Auth::user()->business;
@endphp
@extends('layouts.print')

@section('title', 'Supplier Aging Report')

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Supplier Aging Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Supplier</th>
                <th class="text-right">Current</th>
                <th class="text-right">1-30 Days</th>
                <th class="text-right">31-60 Days</th>
                <th class="text-right">61-90 Days</th>
                <th class="text-right">91-120 Days</th>
                <th class="text-right">120+ Days</th>
                <th class="text-right">Total Outstanding</th>
                <th>Last Payment</th>
                <th class="text-right">Total Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->supplier_name }}</td>
                    <td class="text-right">{{ $row->bucket_current > 0 ? currency($row->bucket_current) : '' }}</td>
                    <td class="text-right">{{ $row->bucket_1_30 > 0 ? currency($row->bucket_1_30) : '' }}</td>
                    <td class="text-right">{{ $row->bucket_31_60 > 0 ? currency($row->bucket_31_60) : '' }}</td>
                    <td class="text-right">{{ $row->bucket_61_90 > 0 ? currency($row->bucket_61_90) : '' }}</td>
                    <td class="text-right">{{ $row->bucket_91_120 > 0 ? currency($row->bucket_91_120) : '' }}</td>
                    <td class="text-right">{{ $row->bucket_120_plus > 0 ? currency($row->bucket_120_plus) : '' }}</td>
                    <td class="text-right">{{ currency($row->total_outstanding) }}</td>
                    <td>{{ $row->last_payment_date ? localDate($row->last_payment_date) : 'N/A' }}</td>
                    <td class="text-right">{{ currency($row->total_balance) }} {{ $row->total_balance_type }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No outstanding balances found</td>
                </tr>
            @endforelse
            <tr>
                <td><strong>Grand Total</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('bucket_current')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('bucket_1_30')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('bucket_31_60')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('bucket_61_90')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('bucket_91_120')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('bucket_120_plus')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('total_outstanding')) }}</strong></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
    ])
@endsection
