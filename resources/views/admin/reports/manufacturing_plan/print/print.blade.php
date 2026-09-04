@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')
@section('title', 'Manufacturing Plan Report')
@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection
@section('content')
    @include('admin.partials.print.header', [
        'business' => $business, 'branch' => null, 'title' => 'Manufacturing Plan Report',
        'doc_no' => '', 'doc_date' => localDate(now()), 'reference' => [], 'print_config' => $print_config,
    ])
    <table class="print-table">
        <thead>
            <tr>
                <th>Plan No.</th><th>Plan Date</th><th>Business</th><th>Branch</th><th>Product</th>
                <th class="text-right">Planned Qty</th><th class="text-right">Produced Qty</th>
                <th class="text-right">Remaining Qty</th><th class="text-right">Progress %</th><th>Status</th>
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
