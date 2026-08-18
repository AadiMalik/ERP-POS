@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">My Salary Slips</h4>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Period</th><th>Net Salary</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse ($payslips as $payslip)
                    <tr>
                        <td>{{ date('F', mktime(0, 0, 0, $payslip->payrollRun->month, 1)) }} {{ $payslip->payrollRun->year }}</td>
                        <td>{{ number_format($payslip->net_salary, 2) }}</td>
                        <td><span class="badge bg-label-{{ $payslip->status == 'paid' ? 'success' : 'secondary' }}">{{ ucfirst($payslip->status) }}</span></td>
                        <td>
                            <a href="{{ url('admin/ess/payslip/' . $payslip->payslip_id) }}" class="btn btn-icon btn-outline-primary" target="_blank"><i class="fa fa-eye"></i></a>
                            <a href="{{ url('admin/ess/payslip/' . $payslip->payslip_id . '/pdf') }}" class="btn btn-icon btn-outline-secondary" target="_blank"><i class="fa fa-download"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">No salary slips yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
