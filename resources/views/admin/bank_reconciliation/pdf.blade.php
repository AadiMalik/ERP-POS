@php
    $accountLabel = trim(($reconciliation->account->code ?? '') . ' - ' . ($reconciliation->account->name ?? ''));
    $matched = $statement_lines->where('match_status', 'matched');
    $bookById = $book_lines->keyBy('journal_entry_detail_id');
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Reconciliation</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h2 { margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; }
        th { background: #f3f3f3; text-align: left; }
        .right { text-align: right; }
        .muted { color: #666; }
    </style>
</head>
<body>
    <h2>Bank Reconciliation</h2>
    <p class="muted">
        {{ $accountLabel }}<br>
        Period: {{ localDate($reconciliation->period_from) }} to {{ localDate($reconciliation->period_to) }}<br>
        Status: {{ ucfirst($reconciliation->status) }}
        @if ($reconciliation->status === 'completed')
            · Completed by {{ $reconciliation->completedBy->name ?? 'N/A' }}
            on {{ optional($reconciliation->completed_at)->format('d-m-Y H:i') }}
        @endif
    </p>

    <table>
        <tr><td>Statement Opening</td><td class="right">{{ number_format($reconciliation->statement_opening_balance, 2) }}</td></tr>
        <tr><td>Statement Closing</td><td class="right">{{ number_format($reconciliation->statement_closing_balance, 2) }}</td></tr>
        <tr><td>Book Balance</td><td class="right">{{ number_format($balances['book_balance'], 2) }}</td></tr>
        <tr><td>Adjusted Book</td><td class="right">{{ number_format($balances['adjusted_book_balance'], 2) }}</td></tr>
        <tr><td><strong>Difference</strong></td><td class="right"><strong>{{ number_format($balances['difference'], 2) }}</strong></td></tr>
    </table>

    <h3>Matched Items</h3>
    <table>
        <thead>
            <tr>
                <th>Stmt Date</th>
                <th>Stmt Ref</th>
                <th class="right">Amt</th>
                <th>ERP Date</th>
                <th>ERP Entry</th>
                <th class="right">Amt</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($matched as $line)
                @php $book = $bookById->get($line->matched_journal_entry_detail_id); @endphp
                <tr>
                    <td>{{ localDate($line->transaction_date) }}</td>
                    <td>{{ $line->reference }} {{ $line->description }}</td>
                    <td class="right">{{ number_format($line->amount, 2) }}</td>
                    <td>{{ $book ? localDate($book->entry_date) : '' }}</td>
                    <td>{{ $book->entry_no ?? '' }}</td>
                    <td class="right">{{ $book ? number_format((float) $book->debit - (float) $book->credit, 2) : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No matched items</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
