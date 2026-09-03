@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Order Tax Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Order Tax Report',
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
                <th>Product</th>
                <th>Variation</th>
                <th>SKU</th>
                <th class="text-right">Tax Rate (%)</th>
                <th class="text-right">Taxable Amount</th>
                <th class="text-right">Tax Amount</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->variation_name }}</td>
                    <td>{{ $row->sku }}</td>
                    <td class="text-right">{{ decimal($row->tax_rate) }}%</td>
                    <td class="text-right">{{ currency($row->taxable_amount) }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
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
