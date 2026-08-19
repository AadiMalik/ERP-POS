@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Pending Payroll Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Pending Payroll Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Period</th>
                <th>Status</th>
                <th>Generated On</th>
                <th class="text-right">Employees</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->period }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $row->generated_at ? localDate($row->generated_at) : '-' }}</td>
                    <td class="text-right">{{ $row->employee_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No pending payroll periods</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
