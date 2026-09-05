@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.payroll_disbursement_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.payroll_disbursement_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_employee_code') }}</th>
                <th>{{ __('reports.col_name') }}</th>
                <th>{{ __('reports.col_department') }}</th>
                <th>{{ __('reports.col_period') }}</th>
                <th class="text-right">Net Salary</th>
                <th>{{ __('reports.col_payment_method') }}</th>
                <th>{{ __('reports.col_bank_account') }}</th>
                <th>{{ __('reports.col_payment_status') }}</th>
                <th>{{ __('reports.col_paid_on') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee?->employee_code }}</td>
                    <td>{{ $row->employee?->user?->name }}</td>
                    <td>{{ $row->employee?->department?->name }}</td>
                    <td>{{ $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-' }}</td>
                    <td class="text-right">{{ currency($row->net_salary) }}</td>
                    <td>{{ $row->employee?->payment_method ? ucfirst($row->employee->payment_method) : '-' }}</td>
                    <td>{{ $row->employee?->bank_account_number }}</td>
                    <td>{{ $row->status == 'paid' ? 'Paid' : 'Unpaid' }}</td>
                    <td>{{ $row->paid_at ? localDate($row->paid_at) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr class="grand-total">
            <td>Total Paid / Unpaid</td>
            <td class="text-right">{{ currency($rows->where('status', 'paid')->sum('net_salary')) }} / {{ currency($rows->where('status', '!=', 'paid')->sum('net_salary')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
