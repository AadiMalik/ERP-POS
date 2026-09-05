@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')
@section('title', __('reports.manufacturing_plan'))
@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection
@section('content')
    @include('admin.partials.print.header', [
        'business' => $business, 'branch' => null, 'title' => __('reports.manufacturing_plan'),
        'doc_no' => '', 'doc_date' => localDate(now()), 'reference' => [], 'print_config' => $print_config,
    ])
    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_plan_no') }}</th><th>{{ __('reports.col_plan_date') }}</th><th>{{ __('reports.col_business') }}</th><th>{{ __('reports.col_branch') }}</th><th>{{ __('reports.col_product') }}</th>
                <th class="text-right">Planned Qty</th><th class="text-right">Produced Qty</th>
                <th class="text-right">Remaining Qty</th><th class="text-right">Progress %</th><th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->plan_no }}</td>
                    <td>{{ $row->plan_date }}</td>
                    <td>{{ $row->business_name }}</td>
                    <td>{{ $row->branch_name }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td class="text-right">{{ decimal($row->planned_quantity) }}</td>
                    <td class="text-right">{{ decimal($row->produced_quantity) }}</td>
                    <td class="text-right">{{ decimal($row->remaining_quantity) }}</td>
                    <td class="text-right">{{ $row->progress_percentage }}</td>
                    <td>{{ $row->status_label }}</td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center">No records found</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
