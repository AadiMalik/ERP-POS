@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Welcome, {{ $employee->user->name ?? '' }}</h4>

    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <strong>Today ({{ now()->format('d M Y') }}):</strong>
                @if ($today_attendance && $today_attendance->check_in_time && $today_attendance->check_out_time)
                <span class="badge bg-label-success">Checked out at {{ date('h:i A', strtotime($today_attendance->check_out_time)) }}</span>
                @elseif ($today_attendance && $today_attendance->check_in_time)
                <span class="badge bg-label-info">Checked in at {{ date('h:i A', strtotime($today_attendance->check_in_time)) }}</span>
                @else
                <span class="badge bg-label-secondary">Not checked in yet</span>
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
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Present Days (This Month)</div><h4>{{ $monthly_summary['present_days'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Absent Days</div><h4>{{ $monthly_summary['absent_days'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Leave Days</div><h4>{{ $monthly_summary['leave_days'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Overtime Hours</div><h4>{{ $monthly_summary['overtime_hours'] }}</h4></div></div></div>
    </div>

    <div class="card">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Recent Leave Requests</h5>
            @can('ess.leave.view')
            <a href="{{ url('admin/ess/leave') }}" class="btn btn-sm btn-outline-primary">View All</a>
            @endcan
        </div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Type</th><th>Start</th><th>End</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($recent_leaves as $leave)
                    <tr>
                        <td>{{ $leave->leaveType->name ?? '-' }}</td>
                        <td>{{ $leave->start_date }}</td>
                        <td>{{ $leave->end_date }}</td>
                        <td><span class="badge bg-label-secondary">{{ ucfirst($leave->status) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">No leave requests yet.</td></tr>
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
