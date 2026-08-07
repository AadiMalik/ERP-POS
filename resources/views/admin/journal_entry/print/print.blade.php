@extends('layouts.print')

@section('title', 'Journal Voucher - ' . ($journal_entry->entry_no ?? ''))

@section('content')
    @php
        $posted = $journal_entry->status === 'posted';
    @endphp

    @include('admin.partials.print.status_badge', ['status' => $journal_entry->status, 'posted' => $posted])

    @include('admin.partials.print.header', [
        'business' => $journal_entry->business,
        'branch' => $journal_entry->branch,
        'title' => 'Journal Voucher',
        'doc_no' => $journal_entry->entry_no,
        'doc_date' => localDate($journal_entry->entry_date),
        'reference' => [
            'Journal' => $journal_entry->journal->name ?? 'N/A',
            'Reference No.' => $journal_entry->reference_no ?? 'N/A',
        ],
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Account</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
            @php
                $total_debit = 0;
                $total_credit = 0;
            @endphp
            @forelse ($journal_entry->journalEntryDetails as $index => $detail)
                @php
                    $total_debit += (float) $detail->debit;
                    $total_credit += (float) $detail->credit;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ trim(($detail->account->code ?? '') . ' ' . ($detail->account->name ?? 'N/A')) }}</td>
                    <td>{{ $detail->description ?? '' }}</td>
                    <td class="text-right">{{ currency($detail->debit) }}</td>
                    <td class="text-right">{{ currency($detail->credit) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">No entries found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="text-right">{{ currency($total_debit) }}</td>
                <td class="text-right">{{ currency($total_credit) }}</td>
            </tr>
        </tfoot>
    </table>

    @if (!empty($journal_entry->description))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $journal_entry->description }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Posted By'],
    ])
@endsection
