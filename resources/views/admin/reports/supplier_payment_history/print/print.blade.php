@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Supplier Payment History Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Supplier Payment History Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Payment Date</th>
                <th>Payment No.</th>
                <th>Supplier</th>
                <th>Method</th>
                <th>Ref. Purchase</th>
                <th>Bank/Cash Account</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Net Payment</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->payment_date) }}</td>
                    <td>{{ $row->payment_no }}</td>
                    <td>{{ $row->supplier->name ?? '' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $row->payment_method)) }}</td>
                    <td>{{ $row->purchase->purchase_no ?? '' }}</td>
                    <td>{{ $row->paymentAccount->name ?? '' }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->net_amount) }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center">No payments found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="6"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('tax_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('discount_amount')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('net_amount')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
