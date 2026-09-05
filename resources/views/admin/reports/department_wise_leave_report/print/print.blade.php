@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.department_wise_leave_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.department_wise_leave_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_department') }}</th>
                <th class="text-right">Total Requests</th>
                <th class="text-right">Approved</th>
                <th class="text-right">Pending</th>
                <th class="text-right">Rejected</th>
                <th class="text-right">Total Days</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->department }}</td>
                    <td class="text-right">{{ $row->total_requests }}</td>
                    <td class="text-right">{{ $row->approved_requests }}</td>
                    <td class="text-right">{{ $row->pending_requests }}</td>
                    <td class="text-right">{{ $row->rejected_requests }}</td>
                    <td class="text-right">{{ $row->total_days }}</td>
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
