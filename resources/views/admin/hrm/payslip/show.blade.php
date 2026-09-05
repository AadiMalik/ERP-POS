@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">{{ __('hrm_payslips.title') }}</h4>
        @can('payslip.print')
        <a href="{{ url('admin/payslip/' . $payslip->payslip_id . '/pdf') }}" class="btn btn-primary" target="_blank">
            <i class="fa fa-download"></i> {{ __('hrm_payslips.download_pdf') }}
        </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <strong>{{ __('common.employee') }}:</strong> {{ $payslip->employee->user->name ?? '-' }}<br>
                    <strong>{{ __('common.code') }}:</strong> {{ $payslip->employee->employee_code }}<br>
                    <strong>{{ __('common.department') }}:</strong> {{ $payslip->employee->department->name ?? '-' }}<br>
                    <strong>{{ __('common.designation') }}:</strong> {{ $payslip->employee->designation->name ?? '-' }}
                </div>
                <div class="col-md-6 text-md-end">
                    <strong>{{ __('common.period') }}:</strong> {{ \Carbon\Carbon::create($payslip->payrollRun->year, $payslip->payrollRun->month, 1)->translatedFormat('F') }} {{ $payslip->payrollRun->year }}<br>
                    <strong>{{ __('hrm_payslips.present_days') }}:</strong> {{ $payslip->present_days }}<br>
                    <strong>{{ __('hrm_payslips.absent_days') }}:</strong> {{ $payslip->absent_days }}<br>
                    <strong>{{ __('hrm_payslips.leave_days') }}:</strong> {{ $payslip->leave_days }}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold">{{ __('hrm_payslips.earnings') }}</h6>
                    <table class="table table-sm">
                        @foreach ($payslip->items->where('type', 'earning') as $item)
                        <tr><td>{{ $item->component_name }}</td><td class="text-end">{{ number_format($item->amount, 2) }}</td></tr>
                        @endforeach
                        <tr class="fw-bold"><td>{{ __('hrm_payslips.total_earnings') }}</td><td class="text-end">{{ number_format($payslip->total_earnings, 2) }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold">{{ __('hrm_payslips.deductions') }}</h6>
                    <table class="table table-sm">
                        @foreach ($payslip->items->where('type', 'deduction') as $item)
                        <tr><td>{{ $item->component_name }}</td><td class="text-end">{{ number_format($item->amount, 2) }}</td></tr>
                        @endforeach
                        <tr class="fw-bold"><td>{{ __('hrm_payslips.total_deductions') }}</td><td class="text-end">{{ number_format($payslip->total_deductions, 2) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="text-end mt-3">
                <h5>{{ __('hrm_payslips.net_salary') }}: {{ number_format($payslip->net_salary, 2) }}</h5>
                <span class="badge bg-label-{{ $payslip->status == 'paid' ? 'success' : 'secondary' }}">{{ $payslip->status == 'paid' ? __('hrm_payslips.status_paid') : __('hrm_payslips.status_generated') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
