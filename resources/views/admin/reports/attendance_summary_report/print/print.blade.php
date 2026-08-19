@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Attendance Summary Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Attendance Summary Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Employee Code</th>
                <th>Name</th>
                <th>Department</th>
                <th>Designation</th>
                <th class="text-right">Present</th>
                <th class="text-right">Absent</th>
                <th class="text-right">Late</th>
                <th class="text-right">Half Day</th>
                <th class="text-right">Leave</th>
                <th class="text-right">Holiday</th>
                <th class="text-right">Early Checkout</th>
                <th class="text-right">Working Hours</th>
                <th class="text-right">Scheduled Days</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee_code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->department }}</td>
                    <td>{{ $row->designation }}</td>
                    <td class="text-right">{{ $row->present_count }}</td>
                    <td class="text-right">{{ $row->absent_count }}</td>
                    <td class="text-right">{{ $row->late_count }}</td>
                    <td class="text-right">{{ $row->half_day_count }}</td>
                    <td class="text-right">{{ $row->leave_count }}</td>
                    <td class="text-right">{{ $row->holiday_count }}</td>
                    <td class="text-right">{{ $row->early_checkout_count }}</td>
                    <td class="text-right">{{ $row->total_working_hours }}</td>
                    <td class="text-right">{{ $row->scheduled_working_days }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
