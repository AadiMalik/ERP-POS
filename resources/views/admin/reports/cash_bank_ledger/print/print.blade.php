@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Cash & Bank Ledger')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Cash & Bank Ledger',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Account</th>
                <th>Date</th>
                <th>Voucher Type</th>
                <th>JV Number</th>
                <th>Reference</th>
                <th>Narration</th>
                <th class="text-right">Receipt</th>
                <th class="text-right">Payment</th>
                <th class="text-right">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($result['rows'] as $row)
                <tr @if (!empty($row->is_summary)) style="font-weight:bold;background:#f5f5f5;" @endif>
                    <td>{{ trim(($row->account_code ?? '') . ' ' . ($row->account_name ?? '')) }}</td>
                    <td>{{ $row->entry_date ? localDate($row->entry_date) : '' }}</td>
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
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Receipts</td>
            <td class="text-right">{{ currency($result['total_receipts']) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Payments</td>
            <td class="text-right">{{ currency($result['total_payments']) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
