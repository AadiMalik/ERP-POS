@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        {{ ucfirst($exit->type) }} - {{ $exit->employee->user->name ?? '-' }}
        <span class="badge bg-label-{{ ['pending' => 'warning', 'approved' => 'info', 'rejected' => 'danger', 'finalized' => 'success'][$exit->status] }}">{{ ucfirst($exit->status) }}</span>
    </h4>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>Employee:</strong> {{ $exit->employee->user->name ?? '-' }}</div>
                <div class="col-md-4"><strong>Request Date:</strong> {{ $exit->request_date }}</div>
                <div class="col-md-4"><strong>Last Working Date:</strong> {{ $exit->last_working_date ?? '-' }}</div>
                <div class="col-md-4 mt-2"><strong>Notice Period:</strong> {{ $exit->notice_period_days }} days</div>
                <div class="col-md-8 mt-2"><strong>Reason:</strong> {{ $exit->reason ?? '-' }}</div>
            </div>

            @can('employee-exit.approve')
            @if ($exit->status == 'pending')
            <div class="d-flex gap-2 mt-4">
                <button type="button" id="approveExit" class="btn btn-success">Approve</button>
                <button type="button" id="rejectExit" class="btn btn-danger">Reject</button>
            </div>
            @endif
            @endcan
        </div>
    </div>

    @if ($exit->status == 'approved' || $exit->status == 'finalized')
    <div class="card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Clearance Checklist</h5>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Area</th>
                        <th>Status</th>
                        <th>Cleared By</th>
                        <th>Remarks</th>
                        @can('employee-clearance.manage')
                        @if ($exit->status == 'approved')
                        <th>Action</th>
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
                            <span class="badge bg-label-success">Cleared</span>
                            @elseif ($clearance->status == 'rejected')
                            <span class="badge bg-label-danger">Rejected</span>
                            @else
                            <span class="badge bg-label-warning">Pending</span>
                            @endif
                        </td>
                        <td>{{ $clearance->clearedBy->name ?? '-' }}</td>
                        <td>{{ $clearance->remarks ?? '-' }}</td>
                        @can('employee-clearance.manage')
                        @if ($exit->status == 'approved')
                        <td>
                            @if ($clearance->status == 'pending')
                            <button type="button" class="btn btn-sm btn-outline-success clearArea" data-id="{{ $clearance->exit_clearance_id }}" data-status="cleared">Clear</button>
                            <button type="button" class="btn btn-sm btn-outline-danger clearArea" data-id="{{ $clearance->exit_clearance_id }}" data-status="rejected">Reject</button>
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
                <label class="fw-semibold">Final Settlement Amount (optional)</label>
                <div class="d-flex gap-2">
                    <input type="number" step="0.01" id="final_settlement_amount" class="form-control" style="max-width:250px;">
                    <button type="button" id="finalizeExit" class="btn btn-primary">Finalize Exit</button>
                </div>
                <small class="form-text text-muted">All clearance areas must be "Cleared" before finalizing. Finalizing deactivates the employee's login.</small>
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
    function exitDecide(status) {
        Swal.fire({
            title: status === 'approved' ? 'Approve this exit request?' : 'Reject this exit request?',
            showCancelButton: true,
            confirmButtonText: 'Yes'
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
            title: 'Finalize this exit? This deactivates the employee\'s login.',
            showCancelButton: true,
            confirmButtonText: 'Yes, finalize'
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
