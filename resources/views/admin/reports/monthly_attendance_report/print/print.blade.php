@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $days = $rows->first()->days ?? [];
@endphp
@extends('layouts.print')

@section('title', __('reports.monthly_attendance_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.monthly_attendance_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table" style="font-size: 9px;">
        <thead>
            <tr>
                <th>{{ __('reports.col_code') }}</th>
                <th>{{ __('reports.col_name') }}</th>
                @foreach (array_keys($days) as $day)
                    <th class="text-center">{{ $day }}</th>
                @endforeach
                <th class="text-right">{{ __('reports.col_present') }}</th>
                <th class="text-right">{{ __('reports.col_absent') }}</th>
                <th class="text-right">{{ __('reports.col_leave') }}</th>
                <th class="text-right">{{ __('reports.col_hours') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee_code }}</td>
                    <td>{{ $row->name }}</td>
                    @foreach ($row->days as $value)
                        <td class="text-center">{{ $value }}</td>
                    @endforeach
                    <td class="text-right">{{ $row->present_count }}</td>
                    <td class="text-right">{{ $row->absent_count }}</td>
                    <td class="text-right">{{ $row->leave_count }}</td>
                    <td class="text-right">{{ $row->total_working_hours }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 6 + count($days) }}" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
