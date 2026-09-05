@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.employee_master_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.employee_master_report'),
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
                <th>{{ __('reports.col_designation') }}</th>
                <th>{{ __('reports.col_branch') }}</th>
                <th>{{ __('reports.col_joining_date') }}</th>
                <th>{{ __('reports.col_employment_type') }}</th>
                <th>{{ __('reports.col_contact') }}</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee_code }}</td>
                    <td>{{ $row->user?->name }}</td>
                    <td>{{ $row->department?->name }}</td>
                    <td>{{ $row->designation?->name }}</td>
                    <td>{{ $row->branch?->name }}</td>
                    <td>{{ localDate($row->joining_date) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', (string) $row->employment_type)) }}</td>
                    <td>{{ $row->user?->phone }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
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
