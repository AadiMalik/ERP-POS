@php
    $exitTypeLabel = $exit->type === 'termination' ? __('hrm_exit.termination') : __('hrm_exit.resignation');
    $statusLabels = [
        'pending' => __('hrm_exit.pending'),
        'approved' => __('hrm_exit.approved'),
        'rejected' => __('hrm_exit.rejected'),
        'finalized' => __('hrm_exit.finalized'),
    ];
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        {{ $exitTypeLabel }} - {{ $exit->employee->user->name ?? '-' }}
        <span class="badge bg-label-{{ ['pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'finalized' => 'success'][$exit->status] }}">{{ $statusLabels[$exit->status] ?? ucfirst($exit->status) }}</span>
    </h4>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>{{ __('common.employee') }}:</strong> {{ $exit->employee->user->name ?? '-' }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_exit.request_date') }}:</strong> {{ $exit->request_date }}</div>
                <div class="col-md-4"><strong>{{ __('hrm_exit.last_working_date') }}:</strong> {{ $exit->last_working_date ?? '-' }}</div>
                <div class="col-md-4 mt-2"><strong>{{ __('hrm_exit.notice_period') }}:</strong> {{ $exit->notice_period_days }} {{ __('hrm_exit.days') }}</div>
                <div class="col-md-8 mt-2"><strong>{{ __('common.reason') }}:</strong> {{ $exit->reason ?? '-' }}</div>
            </div>

            @can('employee-exit.approve')
            @if ($exit->status == 'pending')
            <div class="d-flex gap-2 mt-4">
                <button type="button" id="approveExit" class="btn btn-success">{{ __('hrm_exit.approve') }}</button>
                <button type="button" id="rejectExit" class="btn btn-danger">{{ __('hrm_exit.reject') }}</button>
            </div>
            @endif
            @endcan
        </div>
    </div>

    @if ($exit->status == 'approved' || $exit->status == 'finalized')
    <div class="card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ __('hrm_exit.clearance_checklist') }}</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('hrm_exit.area') }}</th>
                        <th>{{ __('common.status') }}</th>
                        <th>{{ __('hrm_exit.cleared_by') }}</th>
                        <th>{{ __('common.remarks') }}</th>
                        @can('employee-clearance.manage')
                        @if ($exit->status == 'approved')
                        <th>{{ __('common.action') }}</th>
                        @endif
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($exit->clearances as $clearance)
                    <tr>
                        <td>{{ $clearance->area }}</td>
                        <td>
                            @if ($clearance->status == 'cleared')
                            <span class="badge bg-label-success">{{ __('hrm_exit.cleared') }}</span>
                            @elseif ($clearance->status == 'rejected')
                            <span class="badge bg-label-danger">{{ __('hrm_exit.rejected') }}</span>
                            @else
                            <span class="badge bg-label-warning">{{ __('hrm_exit.pending') }}</span>
                            @endif
                        </td>
                        <td>{{ $clearance->clearedBy->name ?? '-' }}</td>
                        <td>{{ $clearance->remarks ?? '-' }}</td>
                        @can('employee-clearance.manage')
                        @if ($exit->status == 'approved')
                        <td>
                            @if ($clearance->status == 'pending')
                            <button type="button" class="btn btn-sm btn-outline-success clearArea" data-id="{{ $clearance->exit_clearance_id }}" data-status="cleared">{{ __('hrm_exit.clear') }}</button>
                            <button type="button" class="btn btn-sm btn-outline-danger clearArea" data-id="{{ $clearance->exit_clearance_id }}" data-status="rejected">{{ __('hrm_exit.reject') }}</button>
                            @endif
                        </td>
                        @endif
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @can('employee-exit.finalize')
            @if ($exit->status == 'approved')
            <div class="mt-4 border-top pt-4">
                <label class="fw-semibold">{{ __('hrm_exit.final_settlement_amount') }}</label>
                <div class="d-flex gap-2">
                    <input type="number" step="0.01" id="final_settlement_amount" class="form-control" style="max-width:250px;">
                    <button type="button" id="finalizeExit" class="btn btn-primary">{{ __('hrm_exit.finalize_exit') }}</button>
                </div>
                <small class="form-text text-muted">{{ __('hrm_exit.finalize_hint') }}</small>
            </div>
            @endif
            @endcan
        </div>
    </div>
    @endif
</div>
@endsection

@section('js')
<script>
    window.i18n_hrm_exit = {
        approve_confirm: @json(__('hrm_exit.approve_confirm')),
        reject_confirm: @json(__('hrm_exit.reject_confirm')),
        finalize_confirm: @json(__('hrm_exit.finalize_confirm')),
        finalize_confirm_button: @json(__('hrm_exit.finalize_confirm_button')),
        yes: @json(__('common.yes')),
        cancel: @json(__('common.cancel')),
    };

    function exitDecide(status) {
        Swal.fire({
            title: status === 'approved' ? window.i18n_hrm_exit.approve_confirm : window.i18n_hrm_exit.reject_confirm,
            showCancelButton: true,
            confirmButtonText: window.i18n_hrm_exit.yes,
            cancelButtonText: window.i18n_hrm_exit.cancel,
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest({
                        url: url_local + '/admin/employee-exit/{{ $exit->employee_exit_id }}/decide',
                        method: 'POST',
                        data: { status: status }
                    })
                    .then((response) => {
                        successMessage(response.Message);
                        setTimeout(() => window.location.reload(), 800);
                    })
                    .catch((err) => errorMessage(err.Message));
            }
        });
    }
    $('#approveExit').click(function() { exitDecide('approved'); });
    $('#rejectExit').click(function() { exitDecide('rejected'); });

    $('.clearArea').click(function() {
        let id = $(this).data('id');
        let status = $(this).data('status');
        ajaxRequest({
                url: url_local + '/admin/exit-clearance/' + id + '/decide',
                method: 'POST',
                data: { status: status }
            })
            .then((response) => {
                successMessage(response.Message);
                setTimeout(() => window.location.reload(), 800);
            })
            .catch((err) => errorMessage(err.Message));
    });

    $('#finalizeExit').click(function() {
        Swal.fire({
            title: window.i18n_hrm_exit.finalize_confirm,
            showCancelButton: true,
            confirmButtonText: window.i18n_hrm_exit.finalize_confirm_button,
            cancelButtonText: window.i18n_hrm_exit.cancel,
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest({
                        url: url_local + '/admin/employee-exit/{{ $exit->employee_exit_id }}/finalize',
                        method: 'POST',
                        data: { final_settlement_amount: $('#final_settlement_amount').val() }
                    })
                    .then((response) => {
                        successMessage(response.Message);
                        setTimeout(() => window.location.reload(), 800);
                    })
                    .catch((err) => errorMessage(err.Message));
            }
        });
    });
</script>
@endsection
