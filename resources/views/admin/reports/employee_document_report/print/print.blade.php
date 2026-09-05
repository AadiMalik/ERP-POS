@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.employee_document_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.employee_document_report'),
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
                <th>{{ __('reports.col_document_type') }}</th>
                <th>{{ __('reports.col_expiry_date') }}</th>
                <th>{{ __('reports.col_expiry_status') }}</th>
                <th>{{ __('reports.col_uploaded_on') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee?->employee_code }}</td>
                    <td>{{ $row->employee?->user?->name }}</td>
                    <td>{{ $row->employee?->department?->name }}</td>
                    <td>{{ $row->document_type }}</td>
                    <td>{{ $row->expiry_date ? localDate($row->expiry_date) : '-' }}</td>
                    <td>{{ $row->expiry_status }}</td>
                    <td>{{ localDate($row->date_created) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
