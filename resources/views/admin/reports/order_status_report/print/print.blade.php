@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Order Status Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Order Status Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Status</th>
                <th class="text-right">Orders</th>
                <th class="text-right">Gross Sales</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Net Sales</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Due</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                    <td class="text-right">{{ $row->order_count }}</td>
                    <td class="text-right">{{ currency($row->gross) }}</td>
                    <td class="text-right">{{ currency($row->discount) }}</td>
                    <td class="text-right">{{ currency($row->tax) }}</td>
                    <td class="text-right">{{ currency($row->net) }}</td>
                    <td class="text-right">{{ currency($row->paid) }}</td>
                    <td class="text-right">{{ currency(max($row->net - $row->paid, 0)) }}</td>
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
