@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_ess.my_salary_slips') }}</h4>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead><tr><th>{{ __('common.period') }}</th><th>{{ __('hrm_ess.net_salary') }}</th><th>{{ __('common.status') }}</th><th>{{ __('common.action') }}</th></tr></thead>
                <tbody>
                    @forelse ($payslips as $payslip)
                    <tr>
                        <td>{{ \Carbon\Carbon::create($payslip->payrollRun->year, $payslip->payrollRun->month, 1)->translatedFormat('F') }} {{ $payslip->payrollRun->year }}</td>
                        <td>{{ number_format($payslip->net_salary, 2) }}</td>
                        <td><span class="badge bg-label-{{ $payslip->status == 'paid' ? 'success' : 'secondary' }}">{{ $payslip->status == 'paid' ? __('hrm_ess.status_paid') : __('hrm_ess.status_generated') }}</span></td>
                        <td>
                            <a href="{{ url('admin/ess/payslip/' . $payslip->payslip_id) }}" class="btn btn-icon btn-outline-primary" target="_blank"><i class="fa fa-eye"></i></a>
                            <a href="{{ url('admin/ess/payslip/' . $payslip->payslip_id . '/pdf') }}" class="btn btn-icon btn-outline-secondary" target="_blank"><i class="fa fa-download"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">{{ __('hrm_ess.no_salary_slips') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
