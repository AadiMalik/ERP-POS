@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.pending_leave_approval_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.pending_leave_approval_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_employee_code') }}</th>
                <th>{{ __('reports.col_name') }}</th>
                <th>{{ __('reports.col_department') }}</th>
                <th>{{ __('reports.col_leave_type') }}</th>
                <th>{{ __('reports.col_start_date') }}</th>
                <th>{{ __('reports.col_end_date') }}</th>
                <th class="text-right">{{ __('reports.col_days') }}</th>
                <th>{{ __('reports.col_requested_on') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee?->employee_code }}</td>
                    <td>{{ $row->employee?->user?->name }}</td>
                    <td>{{ $row->employee?->department?->name }}</td>
                    <td>{{ $row->leaveType?->name }}</td>
                    <td>{{ localDate($row->start_date) }}</td>
                    <td>{{ localDate($row->end_date) }}</td>
                    <td class="text-right">{{ $row->days_count }}</td>
                    <td>{{ localDate($row->date_created) }}</td>
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
