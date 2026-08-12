@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Account Ledger Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Account Ledger Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [
            'Account' => optional($result['account'])->code . ' ' . optional($result['account'])->name,
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Voucher Type</th>
                <th>JV Number</th>
                <th>Reference</th>
                <th>Narration</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="7"><strong>Opening Balance</strong></td>
                <td class="text-right"><strong>{{ currency($result['opening_balance']) }} {{ $result['opening_balance_type'] }}</strong></td>
            </tr>
            @forelse ($result['rows'] as $row)
                <tr>
                    <td>{{ localDate($row->entry_date) }}</td>
                    <td>{{ $row->voucher_name ?? $row->source_type }}</td>
                    <td>{{ $row->entry_no }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ $row->detail_description ?: $row->entry_description }}</td>
                    <td class="text-right">{{ $row->debit > 0 ? currency($row->debit) : '' }}</td>
                    <td class="text-right">{{ $row->credit > 0 ? currency($row->credit) : '' }}</td>
                    <td class="text-right">{{ currency($row->running_balance) }} {{ $row->running_balance_type }}</td>
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
            <td class="text-right">{{ currency($result['total_debit']) }}</td>
        </tr>
        <tr>
            <td>Total Credit</td>
            <td class="text-right">{{ currency($result['total_credit']) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Closing Balance</td>
            <td class="text-right">{{ currency($result['closing_balance']) }} {{ $result['closing_balance_type'] }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
