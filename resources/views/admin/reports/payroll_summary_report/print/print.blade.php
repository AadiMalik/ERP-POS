@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Payroll Summary Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Payroll Summary Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Period</th>
                <th class="text-right">Employees</th>
                <th class="text-right">Gross Salary</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Advance Deduction</th>
                <th class="text-right">Overtime</th>
                <th class="text-right">Net Salary</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ date('F', mktime(0, 0, 0, $row->month, 1)) }} {{ $row->year }}</td>
                    <td class="text-right">{{ $row->employee_count }}</td>
                    <td class="text-right">{{ currency($row->total_gross) }}</td>
                    <td class="text-right">{{ currency($row->total_deductions) }}</td>
                    <td class="text-right">{{ currency($row->total_advance_deduction) }}</td>
                    <td class="text-right">{{ currency($row->total_overtime) }}</td>
                    <td class="text-right">{{ currency($row->total_net_salary) }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr class="grand-total">
            <td>Total Net Salary</td>
            <td class="text-right">{{ currency($rows->sum('total_net_salary')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
