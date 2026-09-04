@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')
@section('title', 'Material Consumption Report')
@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection
@section('content')
    @include('admin.partials.print.header', [
        'business' => $business, 'branch' => null, 'title' => 'Material Consumption Report',
        'doc_no' => '', 'doc_date' => localDate(now()), 'reference' => [], 'print_config' => $print_config,
    ])
    <table class="print-table">
        <thead>
            <tr>
                <th>Date</th><th>Raw Material</th><th>Batch Consumed</th><th class="text-right">Qty</th>
                <th class="text-right">Total Cost</th><th>Warehouse</th><th>Production #</th><th>Plan #</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->date_created) }}</td>
                    <td>{{ $row->raw_material_name }}</td>
                    <td>{{ $row->batch_no }}</td>
                    <td class="text-right">{{ decimal($row->base_quantity) }}</td>
                    <td class="text-right">{{ currency($row->total_cost) }}</td>
                    <td>{{ $row->warehouse_name }}</td>
                    <td>{{ $row->production_no }}</td>
                    <td>{{ $row->plan_no }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
