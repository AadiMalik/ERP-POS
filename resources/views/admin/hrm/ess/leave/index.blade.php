@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_ess.my_leave') }}</h4>

    <div class="row g-3 mb-4">
        @foreach ($balances as $balance)
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">{{ $balance['leave_type'] }}</div>
                    <h5>{{ __('hrm_ess.days_left', ['remaining' => $balance['remaining'], 'entitled' => $balance['entitled']]) }}</h5>
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
                <i class="fa fa-plus"></i> {{ __('hrm_ess.apply_for_leave') }}
            </a>
            @endcan
        </div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>{{ __('common.type') }}</th><th>{{ __('hrm_ess.start') }}</th><th>{{ __('hrm_ess.end') }}</th><th>{{ __('hrm_ess.days') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.action') }}</th></tr></thead>
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
                                $statusLabel = match ($leave->status) {
                                    'pending' => __('hrm_ess.status_pending'),
                                    'approved' => __('hrm_ess.status_approved'),
                                    'rejected' => __('hrm_ess.status_rejected'),
                                    'cancelled' => __('hrm_ess.status_cancelled'),
                                    default => ucfirst($leave->status),
                                };
                            @endphp
                            <span class="badge bg-label-{{ $map[$leave->status] ?? 'secondary' }}">{{ $statusLabel }}</span>
                        </td>
                        <td>
                            @if ($leave->status == 'pending')
                            <button type="button" class="btn btn-sm btn-outline-danger cancelLeaveBtn" data-id="{{ $leave->leave_request_id }}">{{ __('common.cancel') }}</button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">{{ __('hrm_ess.no_leave_requests') }}</td></tr>
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
    window.i18n_hrm_ess = Object.assign(window.i18n_hrm_ess || {}, {
        cancel_leave_confirm: @json(__('hrm_ess.cancel_leave_confirm')),
        yes_cancel_it: @json(__('hrm_ess.yes_cancel_it')),
        leave_cancelled: @json(__('hrm_ess.leave_cancelled'))
    });
    $('.cancelLeaveBtn').click(function() {
        let id = $(this).data('id');
        Swal.fire({
            title: (window.i18n_hrm_ess && window.i18n_hrm_ess.cancel_leave_confirm) || 'Cancel this leave request?',
            showCancelButton: true,
            confirmButtonText: (window.i18n_hrm_ess && window.i18n_hrm_ess.yes_cancel_it) || 'Yes, cancel it'
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest({
                        url: url_local + '/admin/ess/leave/' + id + '/cancel',
                        method: 'POST'
                    })
                    .then(() => {
                        successMessage((window.i18n_hrm_ess && window.i18n_hrm_ess.leave_cancelled) || 'Leave request cancelled.');
                        setTimeout(() => window.location.reload(), 800);
                    })
                    .catch((err) => errorMessage(err.Message));
            }
        });
    });
</script>
@endsection
