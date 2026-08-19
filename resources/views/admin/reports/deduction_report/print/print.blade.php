@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Deduction Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Deduction Report',
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
                <th>Title</th>
                <th class="text-right">Amount</th>
                <th>Recurring</th>
                <th>Effective From</th>
                <th>Effective To</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee?->employee_code }}</td>
                    <td>{{ $row->employee?->user?->name }}</td>
                    <td>{{ $row->employee?->department?->name }}</td>
                    <td>{{ $row->title }}</td>
                    <td class="text-right">{{ currency($row->amount) }}</td>
                    <td>{{ $row->is_recurring ? 'Yes' : 'No' }}</td>
                    <td>{{ localDate($row->effective_from) }}</td>
                    <td>{{ $row->effective_to ? localDate($row->effective_to) : '-' }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
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
            <td>Total Amount</td>
            <td class="text-right">{{ currency($rows->sum('amount')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
