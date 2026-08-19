@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Branch Payroll Cost Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Branch Payroll Cost Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Branch</th>
                <th class="text-right">Employees</th>
                <th class="text-right">Total Earnings</th>
                <th class="text-right">Total Advances</th>
                <th class="text-right">Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->branch }}</td>
                    <td class="text-right">{{ $row->employee_count }}</td>
                    <td class="text-right">{{ currency($row->total_earnings) }}</td>
                    <td class="text-right">{{ currency($row->total_advances) }}</td>
                    <td class="text-right">{{ currency($row->total_cost) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No records found</td>
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
