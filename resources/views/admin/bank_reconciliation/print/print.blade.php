@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve($reconciliation->business_id ?? Auth::user()->business_id);
    $accountLabel = trim(($reconciliation->account->code ?? '') . ' - ' . ($reconciliation->account->name ?? ''));
@endphp
@extends('layouts.print')

@section('title', 'Bank Reconciliation')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => $reconciliation->branch,
        'title' => 'Bank Reconciliation',
        'doc_no' => strtoupper(substr($reconciliation->bank_reconciliation_id, 0, 8)),
        'doc_date' => localDate(now()),
        'reference' => [
            'Account' => $accountLabel,
            'Period' => localDate($reconciliation->period_from) . ' to ' . localDate($reconciliation->period_to),
            'Status' => ucfirst($reconciliation->status),
        ],
        'print_config' => $print_config,
    ])

    <table class="print-totals">
        <tr>
            <td>Statement Opening</td>
            <td class="text-right">{{ currency($reconciliation->statement_opening_balance) }}</td>
        </tr>
        <tr>
            <td>Statement Closing</td>
            <td class="text-right">{{ currency($reconciliation->statement_closing_balance) }}</td>
        </tr>
        <tr>
            <td>Book Balance (as of period end)</td>
            <td class="text-right">{{ currency($balances['book_balance']) }}</td>
        </tr>
        <tr>
            <td>Adjusted Book Balance</td>
            <td class="text-right">{{ currency($balances['adjusted_book_balance']) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Difference</td>
            <td class="text-right">{{ currency($balances['difference']) }}</td>
        </tr>
    </table>

    @if ($reconciliation->status === 'completed')
        <p>
            Completed by {{ $reconciliation->completedBy->name ?? 'N/A' }}
            on {{ optional($reconciliation->completed_at)->format('d-m-Y H:i') }}
        </p>
    @endif

    <h4 style="margin-top:16px;">Matched Items</h4>
    <table class="print-table">
        <thead>
            <tr>
                <th>Stmt Date</th>
                <th>Stmt Ref</th>
                <th class="text-right">Stmt Amt</th>
                <th>ERP Date</th>
                <th>ERP Entry</th>
                <th class="text-right">ERP Amt</th>
            </tr>
        </thead>
        <tbody>
            @php
                $matched = $statement_lines->where('match_status', 'matched');
                $bookById = $book_lines->keyBy('journal_entry_detail_id');
            @endphp
            @forelse ($matched as $line)
                @php $book = $bookById->get($line->matched_journal_entry_detail_id); @endphp
                <tr>
                    <td>{{ localDate($line->transaction_date) }}</td>
                    <td>{{ $line->reference }} {{ $line->description }}</td>
                    <td class="text-right">{{ currency($line->amount) }}</td>
                    <td>{{ $book ? localDate($book->entry_date) : '' }}</td>
                    <td>{{ $book->entry_no ?? '' }} {{ $book->reference_no ?? '' }}</td>
                    <td class="text-right">
                        {{ $book ? currency((float) $book->debit - (float) $book->credit) : '' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No matched items</td></tr>
            @endforelse
        </tbody>
    </table>

    <h4 style="margin-top:16px;">Unmatched ERP (uncleared)</h4>
    <table class="print-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Entry</th>
                <th>Details</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($book_lines->where('is_reconciled', false) as $row)
                <tr>
                    <td>{{ localDate($row->entry_date) }}</td>
                    <td>{{ $row->entry_no }}</td>
                    <td>{{ $row->detail_description ?: $row->entry_description }}</td>
                    <td class="text-right">{{ currency((float) $row->debit - (float) $row->credit) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center">None</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
