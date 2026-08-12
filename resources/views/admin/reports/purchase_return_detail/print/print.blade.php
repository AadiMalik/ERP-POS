@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Purchase Return Detail Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Purchase Return Detail Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Return Date</th>
                <th>Return No.</th>
                <th>Source Ref.</th>
                <th>Supplier</th>
                <th>Warehouse</th>
                <th>Product</th>
                <th>Variation</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Tax</th>
                <th class="text-right">Total</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->purchase_return_date) }}</td>
                    <td>{{ $row->purchase_return_no }}</td>
                    <td>{{ ($row->return_type === 'grn' ? 'GRN: ' : 'Purchase: ') . ($row->source_no ?? '') }}</td>
                    <td>{{ $row->supplier_name }}</td>
                    <td>{{ $row->warehouse_name }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->variation_name }}</td>
                    <td class="text-right">{{ decimal($row->return_quantity) }} {{ $row->unit_name }}</td>
                    <td class="text-right">{{ currency($row->unit_price) }}</td>
                    <td class="text-right">{{ currency($row->discount_amount) }}</td>
                    <td class="text-right">{{ currency($row->tax_amount) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td>{{ $row->status_label }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Qty</td>
            <td class="text-right">{{ decimal($rows->sum('return_quantity')) }}</td>
        </tr>
        <tr>
            <td>Total Discount</td>
            <td class="text-right">{{ currency($rows->sum('discount_amount')) }}</td>
        </tr>
        <tr>
            <td>Total Tax</td>
            <td class="text-right">{{ currency($rows->sum('tax_amount')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Grand Total</td>
            <td class="text-right">{{ currency($rows->sum('total')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
