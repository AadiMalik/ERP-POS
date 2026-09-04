@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')
@section('title', 'Production Report')
@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection
@section('content')
    @include('admin.partials.print.header', [
        'business' => $business, 'branch' => null, 'title' => 'Production Report',
        'doc_no' => '', 'doc_date' => localDate(now()), 'reference' => [], 'print_config' => $print_config,
    ])
    <table class="print-table">
        <thead>
            <tr>
                <th>Production No.</th><th>Plan No.</th><th>Product</th><th>Warehouse</th><th>Batch</th>
                <th class="text-right">Qty</th><th class="text-right">Total Cost</th>
                <th class="text-right">Unit Cost</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->production_no }}</td>
                    <td>{{ $row->plan_no }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->warehouse_name }}</td>
                    <td>{{ $row->batch_no }}</td>
                    <td class="text-right">{{ decimal($row->quantity) }}</td>
                    <td class="text-right">{{ currency($row->total_cost) }}</td>
                    <td class="text-right">{{ currency($row->unit_cost) }}</td>
                    <td>{{ $row->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
