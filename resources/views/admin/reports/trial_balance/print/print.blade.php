@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Trial Balance')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Trial Balance',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Account</th>
                <th>Account Type</th>
                <th class="text-right">Opening Debit</th>
                <th class="text-right">Opening Credit</th>
                <th class="text-right">Period Debit</th>
                <th class="text-right">Period Credit</th>
                <th class="text-right">Closing Debit</th>
                <th class="text-right">Closing Credit</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->account_code }} {{ $row->account_name }}</td>
                    <td>{{ $row->account_type }}</td>
                    <td class="text-right">{{ $row->opening_debit > 0 ? currency($row->opening_debit) : '' }}</td>
                    <td class="text-right">{{ $row->opening_credit > 0 ? currency($row->opening_credit) : '' }}</td>
                    <td class="text-right">{{ $row->period_debit > 0 ? currency($row->period_debit) : '' }}</td>
                    <td class="text-right">{{ $row->period_credit > 0 ? currency($row->period_credit) : '' }}</td>
                    <td class="text-right">{{ $row->closing_debit > 0 ? currency($row->closing_debit) : '' }}</td>
                    <td class="text-right">{{ $row->closing_credit > 0 ? currency($row->closing_credit) : '' }}</td>
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
            <td>Total Opening (Dr / Cr)</td>
            <td class="text-right">{{ currency($rows->sum('opening_debit')) }} / {{ currency($rows->sum('opening_credit')) }}</td>
        </tr>
        <tr>
            <td>Total Period (Dr / Cr)</td>
            <td class="text-right">{{ currency($rows->sum('period_debit')) }} / {{ currency($rows->sum('period_credit')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Closing (Dr / Cr)</td>
            <td class="text-right">{{ currency($rows->sum('closing_debit')) }} / {{ currency($rows->sum('closing_credit')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
