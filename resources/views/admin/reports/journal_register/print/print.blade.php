@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Journal Register')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Journal Register',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>JV Number</th>
                <th>Journal Type</th>
                <th>Reference</th>
                <th>Narration</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->entry_date) }}</td>
                    <td>{{ $row->entry_no }}</td>
                    <td>{{ $row->journal_name ?? $row->journal_short }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="text-right">{{ currency($row->total_debit) }}</td>
                    <td class="text-right">{{ currency($row->total_credit) }}</td>
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
        <tr>
            <td>Total Debit</td>
            <td class="text-right">{{ currency($rows->sum('total_debit')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Credit</td>
            <td class="text-right">{{ currency($rows->sum('total_credit')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
