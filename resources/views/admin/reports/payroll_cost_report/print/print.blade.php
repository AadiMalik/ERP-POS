@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Payroll Cost Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Payroll Cost Report',
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
                <th class="text-right">Total Earnings</th>
                <th class="text-right">Total Deductions</th>
                <th class="text-right">Total Cost</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->period }}</td>
                    <td class="text-right">{{ $row->employee_count }}</td>
                    <td class="text-right">{{ currency($row->total_earnings) }}</td>
                    <td class="text-right">{{ currency($row->total_deductions) }}</td>
                    <td class="text-right">{{ currency($row->total_cost) }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr class="grand-total">
            <td>Total Cost</td>
            <td class="text-right">{{ currency($rows->sum('total_cost')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
