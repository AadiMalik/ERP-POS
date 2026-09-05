@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.employee_asset_return_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.employee_asset_return_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_asset_tag') }}</th>
                <th>{{ __('reports.col_asset_name') }}</th>
                <th>{{ __('reports.col_employee') }}</th>
                <th>{{ __('reports.col_department') }}</th>
                <th>{{ __('reports.col_issue_date') }}</th>
                <th>{{ __('reports.col_expected_return') }}</th>
                <th>{{ __('reports.col_return_date') }}</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->asset?->asset_tag }}</td>
                    <td>{{ $row->asset?->name }}</td>
                    <td>{{ $row->employee?->user?->name }}</td>
                    <td>{{ $row->employee?->department?->name }}</td>
                    <td>{{ localDate($row->issue_date) }}</td>
                    <td>{{ $row->expected_return_date ? localDate($row->expected_return_date) : '-' }}</td>
                    <td>{{ $row->return_date ? localDate($row->return_date) : '-' }}</td>
                    <td>{{ $row->is_overdue ? 'Overdue' : ucfirst($row->status) }}</td>
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
