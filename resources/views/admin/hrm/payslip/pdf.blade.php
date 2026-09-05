<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('hrm_payslips.title') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h2 { margin-bottom: 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 4px 6px; }
        .header-table td { padding: 2px 6px; }
        .items-table th, .items-table td { border: 1px solid #ddd; }
        .text-end { text-align: right; }
        .total-row { font-weight: bold; background: #f5f5f5; }
        .net-salary { font-size: 16px; font-weight: bold; text-align: right; margin-top: 10px; }
    </style>
</head>
<body>
    <h2>{{ __('hrm_payslips.title') }}</h2>
    <p>{{ \Carbon\Carbon::create($payslip->payrollRun->year, $payslip->payrollRun->month, 1)->translatedFormat('F') }} {{ $payslip->payrollRun->year }}</p>

    <table class="header-table">
        <tr>
            <td><strong>{{ __('common.employee') }}:</strong> {{ $payslip->employee->user->name ?? '-' }}</td>
            <td><strong>{{ __('common.code') }}:</strong> {{ $payslip->employee->employee_code }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('common.department') }}:</strong> {{ $payslip->employee->department->name ?? '-' }}</td>
            <td><strong>{{ __('common.designation') }}:</strong> {{ $payslip->employee->designation->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>{{ __('hrm_payslips.present_days') }}:</strong> {{ $payslip->present_days }}</td>
            <td><strong>{{ __('hrm_payslips.absent_days') }}:</strong> {{ $payslip->absent_days }} &nbsp; <strong>{{ __('hrm_payslips.leave_days') }}:</strong> {{ $payslip->leave_days }}</td>
        </tr>
    </table>

    <br>

    <table class="items-table">
        <tr>
            <th width="25%">{{ __('hrm_payslips.earnings') }}</th>
            <th width="25%" class="text-end">{{ __('common.amount') }}</th>
            <th width="25%">{{ __('hrm_payslips.deductions') }}</th>
            <th width="25%" class="text-end">{{ __('common.amount') }}</th>
        </tr>
        @php
            $earnings = $payslip->items->where('type', 'earning')->values();
            $deductions = $payslip->items->where('type', 'deduction')->values();
            $max = max($earnings->count(), $deductions->count());
        @endphp
        @for ($i = 0; $i < $max; $i++)
        <tr>
            <td>{{ $earnings[$i]->component_name ?? '' }}</td>
            <td class="text-end">{{ isset($earnings[$i]) ? number_format($earnings[$i]->amount, 2) : '' }}</td>
            <td>{{ $deductions[$i]->component_name ?? '' }}</td>
            <td class="text-end">{{ isset($deductions[$i]) ? number_format($deductions[$i]->amount, 2) : '' }}</td>
        </tr>
        @endfor
        <tr class="total-row">
            <td>{{ __('hrm_payslips.total_earnings') }}</td>
            <td class="text-end">{{ number_format($payslip->total_earnings, 2) }}</td>
            <td>{{ __('hrm_payslips.total_deductions') }}</td>
            <td class="text-end">{{ number_format($payslip->total_deductions, 2) }}</td>
        </tr>
    </table>

    <div class="net-salary">{{ __('hrm_payslips.net_salary') }}: {{ number_format($payslip->net_salary, 2) }}</div>
</body>
</html>
