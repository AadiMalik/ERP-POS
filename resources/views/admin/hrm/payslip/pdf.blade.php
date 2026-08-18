<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Salary Slip</title>
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
    <h2>Salary Slip</h2>
    <p>{{ date('F', mktime(0, 0, 0, $payslip->payrollRun->month, 1)) }} {{ $payslip->payrollRun->year }}</p>

    <table class="header-table">
        <tr>
            <td><strong>Employee:</strong> {{ $payslip->employee->user->name ?? '-' }}</td>
            <td><strong>Code:</strong> {{ $payslip->employee->employee_code }}</td>
        </tr>
        <tr>
            <td><strong>Department:</strong> {{ $payslip->employee->department->name ?? '-' }}</td>
            <td><strong>Designation:</strong> {{ $payslip->employee->designation->name ?? '-' }}</td>
        </tr>
        <tr>
            <td><strong>Present Days:</strong> {{ $payslip->present_days }}</td>
            <td><strong>Absent Days:</strong> {{ $payslip->absent_days }} &nbsp; <strong>Leave Days:</strong> {{ $payslip->leave_days }}</td>
        </tr>
    </table>

    <br>

    <table class="items-table">
        <tr>
            <th width="25%">Earnings</th>
            <th width="25%" class="text-end">Amount</th>
            <th width="25%">Deductions</th>
            <th width="25%" class="text-end">Amount</th>
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
            <td>Total Earnings</td>
            <td class="text-end">{{ number_format($payslip->total_earnings, 2) }}</td>
            <td>Total Deductions</td>
            <td class="text-end">{{ number_format($payslip->total_deductions, 2) }}</td>
        </tr>
    </table>

    <div class="net-salary">Net Salary: {{ number_format($payslip->net_salary, 2) }}</div>
</body>
</html>
