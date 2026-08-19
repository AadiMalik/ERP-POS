@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $lifecycle = $rows->first();
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #ccc;
            padding: 4px 6px;
        }

        table.data-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    @include('admin.partials.print.pdf_header', [
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
        <table class="data-table">
            <tr><th>Department</th><td>{{ $employee->department?->name ?? '-' }}</td>
                <th>Designation</th><td>{{ $employee->designation?->name ?? '-' }}</td></tr>
            <tr><th>Joining Date</th><td>{{ localDate($employee->joining_date) }}</td>
                <th>Status</th><td>{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</td></tr>
        </table>

        <h4>Attendance Summary</h4>
        <table class="data-table">
            <tr><th>Present</th><td>{{ $lifecycle->attendance_present }}</td>
                <th>Absent</th><td>{{ $lifecycle->attendance_absent }}</td>
                <th>Late</th><td>{{ $lifecycle->attendance_late }}</td>
                <th>Leave</th><td>{{ $lifecycle->attendance_leave }}</td></tr>
        </table>

        <h4>Salary History</h4>
        <table class="data-table">
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

        <h4>Leave History</h4>
        <table class="data-table">
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

        <h4>Advances</h4>
        <table class="data-table">
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

        <h4>Exit / Resignation / Termination</h4>
        @if ($lifecycle->exit)
            <table class="data-table">
                <tr><th>Type</th><td>{{ ucfirst($lifecycle->exit->type) }}</td>
                    <th>Last Working Date</th><td>{{ localDate($lifecycle->exit->last_working_date) }}</td>
                    <th>Status</th><td>{{ ucfirst($lifecycle->exit->status) }}</td></tr>
            </table>
        @else
            <p>No exit record - employee is currently active.</p>
        @endif
    @endif
</body>

</html>
