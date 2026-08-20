@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="erp-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Welcome, {{ $employee->user->name ?? '' }} 👋</h4>
            <p class="text-muted mb-0">Here's your attendance and leave overview.</p>
        </div>
    </div>

    <div class="card mb-4 erp-widget-card">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <strong>Today ({{ now()->format('d M Y') }}):</strong>
                @if ($today_attendance && $today_attendance->check_in_time && $today_attendance->check_out_time)
                <span class="erp-status-dot erp-status-dot--success">Checked out at {{ date('h:i A', strtotime($today_attendance->check_out_time)) }}</span>
                @elseif ($today_attendance && $today_attendance->check_in_time)
                <span class="erp-status-dot erp-status-dot--info">Checked in at {{ date('h:i A', strtotime($today_attendance->check_in_time)) }}</span>
                @else
                <span class="erp-status-dot erp-status-dot--secondary">Not checked in yet</span>
                @endif
            </div>
            @can('ess.attendance.manage')
            <div>
                @if (!$today_attendance || !$today_attendance->check_in_time)
                <button type="button" id="checkInBtn" class="btn btn-success">Check In</button>
                @elseif (!$today_attendance->check_out_time)
                <button type="button" id="checkOutBtn" class="btn btn-danger">Check Out</button>
                @else
                <button type="button" class="btn btn-outline-secondary" disabled>Done for today</button>
                @endif
            </div>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 erp-kpi-card" style="--erp-kpi-color: var(--bs-success); --erp-kpi-color-rgb: var(--bs-success-rgb);">
                <div class="card-body d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="erp-kpi-label text-muted">Present Days</span>
                        <h4 class="erp-kpi-value mb-0">{{ $monthly_summary['present_days'] }}</h4>
                    </div>
                    <div class="erp-kpi-icon"><i class="fa fa-check"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 erp-kpi-card" style="--erp-kpi-color: var(--bs-danger); --erp-kpi-color-rgb: var(--bs-danger-rgb);">
                <div class="card-body d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="erp-kpi-label text-muted">Absent Days</span>
                        <h4 class="erp-kpi-value mb-0">{{ $monthly_summary['absent_days'] }}</h4>
                    </div>
                    <div class="erp-kpi-icon"><i class="fa fa-times"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 erp-kpi-card" style="--erp-kpi-color: var(--bs-warning); --erp-kpi-color-rgb: var(--bs-warning-rgb);">
                <div class="card-body d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="erp-kpi-label text-muted">Leave Days</span>
                        <h4 class="erp-kpi-value mb-0">{{ $monthly_summary['leave_days'] }}</h4>
                    </div>
                    <div class="erp-kpi-icon"><i class="fa fa-calendar-minus"></i></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card h-100 erp-kpi-card" style="--erp-kpi-color: var(--bs-info); --erp-kpi-color-rgb: var(--bs-info-rgb);">
                <div class="card-body d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <span class="erp-kpi-label text-muted">Overtime Hours</span>
                        <h4 class="erp-kpi-value mb-0">{{ $monthly_summary['overtime_hours'] }}</h4>
                    </div>
                    <div class="erp-kpi-icon"><i class="fa fa-clock"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Leave Requests</h5>
            @can('ess.leave.view')
            <a href="{{ url('admin/ess/leave') }}" class="btn btn-sm btn-outline-primary">View All</a>
            @endcan
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Type</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($recent_leaves as $leave)
                    <tr>
                        <td>{{ $leave->leaveType->name ?? '-' }}</td>
                        <td>{{ $leave->start_date }}</td>
                        <td>{{ $leave->end_date }}</td>
                        <td>
                            @php
                                $leaveStatus = strtolower($leave->status);
                                $leaveDotClass = match (true) {
                                    in_array($leaveStatus, ['approved']) => 'erp-status-dot--success',
                                    in_array($leaveStatus, ['pending']) => 'erp-status-dot--warning',
                                    in_array($leaveStatus, ['rejected', 'cancelled']) => 'erp-status-dot--danger',
                                    default => 'erp-status-dot--secondary',
                                };
                            @endphp
                            <span class="erp-status-dot {{ $leaveDotClass }}">{{ ucfirst($leave->status) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4">
                            <div class="erp-empty-state">
                                <i class="fa fa-calendar-check"></i>
                                No leave requests yet.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
@if (session('success'))
<script>
    successMessage("{{ session('success') }}");
</script>
@endif
<script>
    $('#checkInBtn').click(function() {
        ajaxRequest({ url: url_local + '/admin/ess/attendance/check-in', method: 'POST' })
            .then((response) => {
                successMessage(response.Message);
                setTimeout(() => window.location.reload(), 800);
            })
            .catch((err) => errorMessage(err.Message));
    });
    $('#checkOutBtn').click(function() {
        ajaxRequest({ url: url_local + '/admin/ess/attendance/check-out', method: 'POST' })
            .then((response) => {
                successMessage(response.Message);
                setTimeout(() => window.location.reload(), 800);
            })
            .catch((err) => errorMessage(err.Message));
    });
</script>
@endsection
