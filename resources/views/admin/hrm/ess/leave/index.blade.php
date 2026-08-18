@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">My Leave</h4>

    <div class="row g-3 mb-4">
        @foreach ($balances as $balance)
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">{{ $balance['leave_type'] }}</div>
                    <h5>{{ $balance['remaining'] }} / {{ $balance['entitled'] }} days left</h5>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            @can('ess.leave.apply')
            <a href="{{ url('admin/ess/leave/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i> Apply for Leave
            </a>
            @endcan
        </div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Type</th><th>Start</th><th>End</th><th>Days</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($leave_requests as $leave)
                    <tr>
                        <td>{{ $leave->leaveType->name ?? '-' }}</td>
                        <td>{{ $leave->start_date }}</td>
                        <td>{{ $leave->end_date }}</td>
                        <td>{{ $leave->days_count }}</td>
                        <td>
                            @php
                                $map = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
                            @endphp
                            <span class="badge bg-label-{{ $map[$leave->status] ?? 'secondary' }}">{{ ucfirst($leave->status) }}</span>
                        </td>
                        <td>
                            @if ($leave->status == 'pending')
                            <button type="button" class="btn btn-sm btn-outline-danger cancelLeaveBtn" data-id="{{ $leave->leave_request_id }}">Cancel</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">No leave requests yet.</td></tr>
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
@if (session('error'))
<script>
    errorMessage("{{ session('error') }}");
</script>
@endif
<script>
    $('.cancelLeaveBtn').click(function() {
        let id = $(this).data('id');
        Swal.fire({
            title: 'Cancel this leave request?',
            showCancelButton: true,
            confirmButtonText: 'Yes, cancel it'
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest({
                        url: url_local + '/admin/ess/leave/' + id + '/cancel',
                        method: 'POST'
                    })
                    .then(() => {
                        successMessage('Leave request cancelled.');
                        setTimeout(() => window.location.reload(), 800);
                    })
                    .catch((err) => errorMessage(err.Message));
            }
        });
    });
</script>
@endsection
