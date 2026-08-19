@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Department-wise Employee Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Department-wise Employee Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Department</th>
                <th class="text-right">Total Employees</th>
                <th class="text-right">Active</th>
                <th class="text-right">On Leave</th>
                <th class="text-right">Resigned</th>
                <th class="text-right">Terminated</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td class="text-right">{{ $row->total_employees }}</td>
                    <td class="text-right">{{ $row->active_employees }}</td>
                    <td class="text-right">{{ $row->on_leave_employees }}</td>
                    <td class="text-right">{{ $row->resigned_employees }}</td>
                    <td class="text-right">{{ $row->terminated_employees }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
