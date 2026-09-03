@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Top Selling Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Top Selling Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Rank</th>
                <th>Product</th>
                <th>Variation</th>
                <th>SKU</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Net Sales</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->rank }}</td>
                    <td>{{ $row->product_name ?? '' }}</td>
                    <td>{{ $row->variation_name ?? '' }}</td>
                    <td>{{ $row->sku ?? '' }}</td>
                    <td class="text-right">{{ decimal($row->total_qty) }}</td>
                    <td class="text-right">{{ currency($row->net) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
