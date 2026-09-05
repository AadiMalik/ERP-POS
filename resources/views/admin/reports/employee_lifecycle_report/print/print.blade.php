@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $lifecycle = $rows->first();
@endphp
@extends('layouts.print')

@section('title', __('reports.employee_lifecycle_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.employee_lifecycle_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    @if (!$lifecycle)
        <p>No employee selected.</p>
    @else
        @php $employee = $lifecycle->employee; @endphp
        <h4>{{ $employee->user?->name }} ({{ $employee->employee_code }})</h4>
        <table class="print-table">
            <tr><th>{{ __('reports.col_department') }}</th><td>{{ $employee->department?->name ?? '-' }}</td>
                <th>{{ __('reports.col_designation') }}</th><td>{{ $employee->designation?->name ?? '-' }}</td></tr>
            <tr><th>{{ __('reports.col_joining_date') }}</th><td>{{ localDate($employee->joining_date) }}</td>
                <th>{{ __('reports.col_status') }}</th><td>{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</td></tr>
        </table>

        <h5 style="margin-top:12px;">Attendance Summary</h5>
        <table class="print-table">
            <tr><th>{{ __('reports.col_present') }}</th><td>{{ $lifecycle->attendance_present }}</td>
                <th>{{ __('reports.col_absent') }}</th><td>{{ $lifecycle->attendance_absent }}</td>
                <th>{{ __('reports.col_late') }}</th><td>{{ $lifecycle->attendance_late }}</td>
                <th>{{ __('reports.col_leave') }}</th><td>{{ $lifecycle->attendance_leave }}</td></tr>
        </table>

        <h5 style="margin-top:12px;">Salary History</h5>
        <table class="print-table">
            <thead><tr><th>{{ __('reports.col_effective_from') }}</th><th>{{ __('reports.col_basic_salary') }}</th><th>{{ __('reports.col_status') }}</th></tr></thead>
            <tbody>
                @forelse ($lifecycle->salary_history as $structure)
                    <tr>
                        <td>{{ localDate($structure->effective_from) }}</td>
                        <td>{{ currency($structure->basic_salary) }}</td>
                        <td>{{ ucfirst($structure->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center">No salary structure recorded</td></tr>
                @endforelse
            </tbody>
        </table>

        <h5 style="margin-top:12px;">Leave History</h5>
        <table class="print-table">
            <thead><tr><th>{{ __('reports.col_leave_type') }}</th><th>{{ __('reports.col_start_date') }}</th><th>{{ __('reports.col_end_date') }}</th><th>{{ __('reports.col_days') }}</th><th>{{ __('reports.col_status') }}</th></tr></thead>
            <tbody>
                @forelse ($lifecycle->leave_requests as $leave)
                    <tr>
                        <td>{{ $leave->leaveType?->name }}</td>
                        <td>{{ localDate($leave->start_date) }}</td>
                        <td>{{ localDate($leave->end_date) }}</td>
                        <td>{{ $leave->days_count }}</td>
                        <td>{{ ucfirst($leave->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center">No leave requests</td></tr>
                @endforelse
            </tbody>
        </table>

        <h5 style="margin-top:12px;">Advances</h5>
        <table class="print-table">
            <thead><tr><th>{{ __('reports.col_request_date') }}</th><th>{{ __('reports.col_amount') }}</th><th>{{ __('reports.col_remaining_balance') }}</th><th>{{ __('reports.col_status') }}</th></tr></thead>
            <tbody>
                @forelse ($lifecycle->advances as $advance)
                    <tr>
                        <td>{{ localDate($advance->request_date) }}</td>
                        <td>{{ currency($advance->amount) }}</td>
                        <td>{{ currency($advance->remaining_balance) }}</td>
                        <td>{{ ucfirst($advance->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center">No advances</td></tr>
                @endforelse
            </tbody>
        </table>

        <h5 style="margin-top:12px;">Exit / Resignation / Termination</h5>
        @if ($lifecycle->exit)
            <table class="print-table">
                <tr><th>{{ __('reports.col_type') }}</th><td>{{ ucfirst($lifecycle->exit->type) }}</td>
                    <th>{{ __('reports.col_last_working_date') }}</th><td>{{ localDate($lifecycle->exit->last_working_date) }}</td>
                    <th>{{ __('reports.col_status') }}</th><td>{{ ucfirst($lifecycle->exit->status) }}</td></tr>
            </table>
        @else
            <p>No exit record - employee is currently active.</p>
        @endif
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
