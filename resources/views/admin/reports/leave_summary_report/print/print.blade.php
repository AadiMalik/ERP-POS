@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Leave Summary Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Leave Summary Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Employee Code</th>
                <th>Name</th>
                <th>Department</th>
                <th>Leave Type</th>
                <th class="text-right">Entitlement</th>
                <th class="text-right">Used</th>
                <th class="text-right">Pending</th>
                <th class="text-right">Rejected</th>
                <th class="text-right">Remaining</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee_code }}</td>
                    <td>{{ $row->name }}</td>
                    <td>{{ $row->department }}</td>
                    <td>{{ $row->leave_type }}</td>
                    <td class="text-right">{{ $row->entitlement }}</td>
                    <td class="text-right">{{ $row->used }}</td>
                    <td class="text-right">{{ $row->pending }}</td>
                    <td class="text-right">{{ $row->rejected }}</td>
                    <td class="text-right">{{ $row->remaining }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
