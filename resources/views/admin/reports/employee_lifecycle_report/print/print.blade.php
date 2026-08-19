@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $lifecycle = $rows->first();
@endphp
@extends('layouts.print')

@section('title', 'Employee Lifecycle Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Employee Lifecycle Report',
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
            <tr><th>Department</th><td>{{ $employee->department?->name ?? '-' }}</td>
                <th>Designation</th><td>{{ $employee->designation?->name ?? '-' }}</td></tr>
            <tr><th>Joining Date</th><td>{{ localDate($employee->joining_date) }}</td>
                <th>Status</th><td>{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</td></tr>
        </table>

        <h5 style="margin-top:12px;">Attendance Summary</h5>
        <table class="print-table">
            <tr><th>Present</th><td>{{ $lifecycle->attendance_present }}</td>
                <th>Absent</th><td>{{ $lifecycle->attendance_absent }}</td>
                <th>Late</th><td>{{ $lifecycle->attendance_late }}</td>
                <th>Leave</th><td>{{ $lifecycle->attendance_leave }}</td></tr>
        </table>

        <h5 style="margin-top:12px;">Salary History</h5>
        <table class="print-table">
            <thead><tr><th>Effective From</th><th>Basic Salary</th><th>Status</th></tr></thead>
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
            <thead><tr><th>Leave Type</th><th>Start Date</th><th>End Date</th><th>Days</th><th>Status</th></tr></thead>
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
            <thead><tr><th>Request Date</th><th>Amount</th><th>Remaining Balance</th><th>Status</th></tr></thead>
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
                <tr><th>Type</th><td>{{ ucfirst($lifecycle->exit->type) }}</td>
                    <th>Last Working Date</th><td>{{ localDate($lifecycle->exit->last_working_date) }}</td>
                    <th>Status</th><td>{{ ucfirst($lifecycle->exit->status) }}</td></tr>
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
