@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        Payroll - {{ date('F', mktime(0, 0, 0, $run->month, 1)) }} {{ $run->year }}
        <span class="badge bg-label-{{ ['draft' => 'warning', 'finalized' => 'info', 'paid' => 'success'][$run->status] }}">{{ ucfirst($run->status) }}</span>
    </h4>

    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>Total Payslips:</strong> {{ $run->payslips->count() }} &nbsp; | &nbsp;
                <strong>Total Amount:</strong> {{ number_format($run->total_amount, 2) }}
            </div>
            <div class="d-flex gap-2">
                @can('payroll.finalize')
                @if ($run->status == 'draft')
                <button type="button" id="finalizeBtn" class="btn btn-info">Finalize</button>
                @endif
                @endcan
                @can('payroll.reopen')
                @if ($run->status == 'finalized')
                <button type="button" id="reopenBtn" class="btn btn-outline-secondary">Reopen</button>
                @endif
                @endcan
                @can('payroll.pay')
                @if ($run->status == 'finalized')
                <button type="button" id="payBtn" class="btn btn-success">Mark Paid</button>
                @endif
                @endcan
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Basic</th>
                            <th>Earnings</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Leave</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($run->payslips as $payslip)
                        <tr>
                            <td>{{ $payslip->employee->user->name ?? '-' }}</td>
                            <td>{{ number_format($payslip->basic_salary, 2) }}</td>
                            <td>{{ number_format($payslip->total_earnings, 2) }}</td>
                            <td>{{ number_format($payslip->total_deductions, 2) }}</td>
                            <td><strong>{{ number_format($payslip->net_salary, 2) }}</strong></td>
                            <td>{{ $payslip->present_days }}</td>
                            <td>{{ $payslip->absent_days }}</td>
                            <td>{{ $payslip->leave_days }}</td>
                            <td>
                                @if ($payslip->status == 'paid')
                                <span class="badge bg-label-success">Paid</span>
                                @else
                                <span class="badge bg-label-secondary">Generated</span>
                                @endif
                            </td>
                            <td>
                                @can('payslip.view')
                                <a href="{{ url('admin/payslip/' . $payslip->payslip_id) }}" class="btn btn-icon btn-outline-primary" target="_blank">
                                    <i class="fa fa-eye"></i>
                                </a>
                                @endcan
                                @can('payslip.print')
                                <a href="{{ url('admin/payslip/' . $payslip->payslip_id . '/pdf') }}" class="btn btn-icon btn-outline-secondary" target="_blank">
                                    <i class="fa fa-download"></i>
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted">No payslips generated yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    function payrollAction(action, confirmText) {
        Swal.fire({
            title: confirmText,
            showCancelButton: true,
            confirmButtonText: 'Yes'
        }).then((result) => {
            if (result.isConfirmed) {
                ajaxRequest({
                        url: url_local + '/admin/payroll/{{ $run->payroll_run_id }}/' + action,
                        method: 'POST'
                    })
                    .then((response) => {
                        successMessage(response.Message);
                        setTimeout(() => window.location.reload(), 800);
                    })
                    .catch((err) => {
                        errorMessage(err.Message);
                    });
            }
        });
    }
    $('#finalizeBtn').click(function() {
        payrollAction('finalize', 'Finalize this payroll run? Payslips will be locked from further recalculation.');
    });
    $('#reopenBtn').click(function() {
        payrollAction('reopen', 'Reopen this payroll run for corrections?');
    });
    $('#payBtn').click(function() {
        payrollAction('pay', 'Mark this payroll as paid? This recovers due advance installments and cannot be undone.');
    });
</script>
@endsection
