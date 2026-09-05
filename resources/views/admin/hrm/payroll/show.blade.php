@extends('layouts.app')
@section('content')
@php
    $statusLabels = [
        'draft' => __('hrm_payroll.status_draft'),
        'finalized' => __('hrm_payroll.status_finalized'),
        'paid' => __('hrm_payroll.status_paid'),
    ];
    $monthName = \Carbon\Carbon::create($run->year, $run->month, 1)->translatedFormat('F');
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        {{ __('hrm_payroll.heading', ['month' => $monthName, 'year' => $run->year]) }}
        <span class="badge bg-label-{{ ['draft' => 'warning', 'finalized' => 'info', 'paid' => 'success'][$run->status] }}">{{ $statusLabels[$run->status] ?? ucfirst($run->status) }}</span>
    </h4>

    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>{{ __('hrm_payroll.total_payslips') }}:</strong> {{ $run->payslips->count() }} &nbsp; | &nbsp;
                <strong>{{ __('hrm_payroll.total_amount') }}:</strong> {{ number_format($run->total_amount, 2) }}
            </div>
            <div class="d-flex gap-2">
                @can('payroll.finalize')
                @if ($run->status == 'draft')
                <button type="button" id="finalizeBtn" class="btn btn-info">{{ __('hrm_payroll.finalize') }}</button>
                @endif
                @endcan
                @can('payroll.reopen')
                @if ($run->status == 'finalized')
                <button type="button" id="reopenBtn" class="btn btn-outline-secondary">{{ __('hrm_payroll.reopen') }}</button>
                @endif
                @endcan
                @can('payroll.pay')
                @if ($run->status == 'finalized')
                <button type="button" id="payBtn" class="btn btn-success">{{ __('hrm_payroll.mark_paid') }}</button>
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
                            <th>{{ __('common.employee') }}</th>
                            <th>{{ __('hrm_payroll.basic') }}</th>
                            <th>{{ __('hrm_payroll.earnings') }}</th>
                            <th>{{ __('hrm_payroll.deductions') }}</th>
                            <th>{{ __('hrm_payroll.net_salary') }}</th>
                            <th>{{ __('hrm_payroll.present') }}</th>
                            <th>{{ __('hrm_payroll.absent') }}</th>
                            <th>{{ __('hrm_payroll.leave') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
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
                                <span class="badge bg-label-success">{{ __('hrm_payroll.status_paid') }}</span>
                                @else
                                <span class="badge bg-label-secondary">{{ __('hrm_payroll.generated') }}</span>
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
                        <tr><td colspan="10" class="text-center text-muted">{{ __('hrm_payroll.no_payslips') }}</td></tr>
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
    window.i18n_hrm_payroll = {
        confirm_finalize: @json(__('hrm_payroll.confirm_finalize')),
        confirm_reopen: @json(__('hrm_payroll.confirm_reopen')),
        confirm_pay: @json(__('hrm_payroll.confirm_pay')),
        yes: @json(__('common.yes'))
    };
    function payrollAction(action, confirmText) {
        Swal.fire({
            title: confirmText,
            showCancelButton: true,
            confirmButtonText: (window.i18n_hrm_payroll && window.i18n_hrm_payroll.yes) || 'Yes'
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
        payrollAction('finalize', (window.i18n_hrm_payroll && window.i18n_hrm_payroll.confirm_finalize) || 'Finalize this payroll run? Payslips will be locked from further recalculation.');
    });
    $('#reopenBtn').click(function() {
        payrollAction('reopen', (window.i18n_hrm_payroll && window.i18n_hrm_payroll.confirm_reopen) || 'Reopen this payroll run for corrections?');
    });
    $('#payBtn').click(function() {
        payrollAction('pay', (window.i18n_hrm_payroll && window.i18n_hrm_payroll.confirm_pay) || 'Mark this payroll as paid? This recovers due advance installments and cannot be undone.');
    });
</script>
@endsection
