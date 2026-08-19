@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Leave Type-wise Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Leave Type-wise Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Leave Type</th>
                <th class="text-right">Total Requests</th>
                <th class="text-right">Approved</th>
                <th class="text-right">Pending</th>
                <th class="text-right">Rejected</th>
                <th class="text-right">Total Days</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->name }}</td>
                    <td class="text-right">{{ $row->total_requests }}</td>
                    <td class="text-right">{{ $row->approved_requests }}</td>
                    <td class="text-right">{{ $row->pending_requests }}</td>
                    <td class="text-right">{{ $row->rejected_requests }}</td>
                    <td class="text-right">{{ $row->total_days ?? 0 }}</td>
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
