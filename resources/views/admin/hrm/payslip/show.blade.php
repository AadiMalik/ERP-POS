@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">Salary Slip</h4>
        @can('payslip.print')
        <a href="{{ url('admin/payslip/' . $payslip->payslip_id . '/pdf') }}" class="btn btn-primary" target="_blank">
            <i class="fa fa-download"></i> Download PDF
        </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <strong>Employee:</strong> {{ $payslip->employee->user->name ?? '-' }}<br>
                    <strong>Code:</strong> {{ $payslip->employee->employee_code }}<br>
                    <strong>Department:</strong> {{ $payslip->employee->department->name ?? '-' }}<br>
                    <strong>Designation:</strong> {{ $payslip->employee->designation->name ?? '-' }}
                </div>
                <div class="col-md-6 text-md-end">
                    <strong>Period:</strong> {{ date('F', mktime(0, 0, 0, $payslip->payrollRun->month, 1)) }} {{ $payslip->payrollRun->year }}<br>
                    <strong>Present Days:</strong> {{ $payslip->present_days }}<br>
                    <strong>Absent Days:</strong> {{ $payslip->absent_days }}<br>
                    <strong>Leave Days:</strong> {{ $payslip->leave_days }}
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold">Earnings</h6>
                    <table class="table table-sm">
                        @foreach ($payslip->items->where('type', 'earning') as $item)
                        <tr><td>{{ $item->component_name }}</td><td class="text-end">{{ number_format($item->amount, 2) }}</td></tr>
                        @endforeach
                        <tr class="fw-bold"><td>Total Earnings</td><td class="text-end">{{ number_format($payslip->total_earnings, 2) }}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold">Deductions</h6>
                    <table class="table table-sm">
                        @foreach ($payslip->items->where('type', 'deduction') as $item)
                        <tr><td>{{ $item->component_name }}</td><td class="text-end">{{ number_format($item->amount, 2) }}</td></tr>
                        @endforeach
                        <tr class="fw-bold"><td>Total Deductions</td><td class="text-end">{{ number_format($payslip->total_deductions, 2) }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="text-end mt-3">
                <h5>Net Salary: {{ number_format($payslip->net_salary, 2) }}</h5>
                <span class="badge bg-label-{{ $payslip->status == 'paid' ? 'success' : 'secondary' }}">{{ ucfirst($payslip->status) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
