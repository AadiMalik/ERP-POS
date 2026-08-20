@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Employee Lifecycle Report
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
                @if ($lifecycle)
                    <div class="d-flex gap-2">
                        @canAccess('reports.employee-lifecycle-report.print')
                        <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                            <i class="fa fa-print"></i> Print
                        </a>
                        @endcanAccess
                        @canAccess('reports.employee-lifecycle-report.pdf')
                        <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                            <i class="fa fa-file-pdf"></i> PDF
                        </a>
                        @endcanAccess
                        @canAccess('reports.employee-lifecycle-report.export')
                        <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                            <i class="fa fa-file-excel"></i> Excel
                        </a>
                        @endcanAccess
                    </div>
                @endif
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Employee</label>
                            <select id="employee_id" class="form-select">
                                <option value="">--Select Employee--</option>
                                @foreach ($employees as $item)
                                    <option value="{{ $item->employee_id }}" {{ request('employee_id') == $item->employee_id ? 'selected' : '' }}>
                                        {{ $item->user?->name }} ({{ $item->employee_code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                View
                            </button>
                        </div>
                    </div>
                </div>

                @if (!$lifecycle)
                    <div class="p-4 text-center text-muted">Select an employee to view their lifecycle.</div>
                @else
                    @php $employee = $lifecycle->employee; @endphp
                    <div class="p-4">
                        <h5>{{ $employee->user?->name }} ({{ $employee->employee_code }})</h5>
                        <table class="table table-bordered table-sm w-auto">
                            <tr><th>Department</th><td>{{ $employee->department?->name ?? '-' }}</td>
                                <th>Designation</th><td>{{ $employee->designation?->name ?? '-' }}</td></tr>
                            <tr><th>Joining Date</th><td>{{ localDate($employee->joining_date) }}</td>
                                <th>Employment Type</th><td>{{ ucfirst(str_replace('_', ' ', (string) $employee->employment_type)) }}</td></tr>
                            <tr><th>Current Status</th><td colspan="3">{{ ucfirst(str_replace('_', ' ', $employee->status)) }}</td></tr>
                        </table>

                        <h6 class="mt-4">Attendance Summary</h6>
                        <table class="table table-bordered table-sm w-auto">
                            <tr><th>Present</th><td>{{ $lifecycle->attendance_present }}</td>
                                <th>Absent</th><td>{{ $lifecycle->attendance_absent }}</td>
                                <th>Late</th><td>{{ $lifecycle->attendance_late }}</td>
                                <th>Leave</th><td>{{ $lifecycle->attendance_leave }}</td></tr>
                        </table>

                        <h6 class="mt-4">Salary History</h6>
                        <table class="table table-bordered table-sm">
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

                        <h6 class="mt-4">Leave History</h6>
                        <table class="table table-bordered table-sm">
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

                        <h6 class="mt-4">Advances</h6>
                        <table class="table table-bordered table-sm">
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

                        <h6 class="mt-4">Exit / Resignation / Termination</h6>
                        @if ($lifecycle->exit)
                            <table class="table table-bordered table-sm w-auto">
                                <tr><th>Type</th><td>{{ ucfirst($lifecycle->exit->type) }}</td>
                                    <th>Last Working Date</th><td>{{ localDate($lifecycle->exit->last_working_date) }}</td>
                                    <th>Status</th><td>{{ ucfirst($lifecycle->exit->status) }}</td></tr>
                            </table>
                        @else
                            <p class="text-muted">No exit record - employee is currently active.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                employee_id: $('#employee_id').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #employee_id').select2();
        });

        $('#search_btn').click(function() {
            window.location.href = buildReportUrl('/admin/reports/employee-lifecycle-report');
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/employee-lifecycle-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/employee-lifecycle-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/employee-lifecycle-report/export');
        });
    </script>
@endsection
