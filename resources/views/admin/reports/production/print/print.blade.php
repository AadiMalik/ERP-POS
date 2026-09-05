@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')
@section('title', __('reports.production'))
@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection
@section('content')
    @include('admin.partials.print.header', [
        'business' => $business, 'branch' => null, 'title' => __('reports.production'),
        'doc_no' => '', 'doc_date' => localDate(now()), 'reference' => [], 'print_config' => $print_config,
    ])
    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_production_no') }}</th><th>{{ __('reports.col_plan_no') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_batch') }}</th>
                <th class="text-right">{{ __('reports.col_qty') }}</th><th class="text-right">Total Cost</th>
                <th class="text-right">{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_status') }}</th>
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
