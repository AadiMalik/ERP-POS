@php
    $isDraft = $reconciliation->isDraft();
    $recId = $reconciliation->bank_reconciliation_id;
    $accountLabel = trim(($reconciliation->account->code ?? '') . ' - ' . ($reconciliation->account->name ?? ''));
@endphp
@extends('layouts.app')
@section('css')
    <style>
        .br-diff-ok { color: #198754; font-weight: 700; }
        .br-diff-bad { color: #dc3545; font-weight: 700; }
        .br-panel { max-height: 520px; overflow-y: auto; }
        .br-row-selected { background: #e7f1ff !important; }
        .br-carry { font-size: 0.75rem; color: #6c757d; }
    </style>
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
            <div>
                <h4 class="fw-bold mb-1">
                    <a href="{{ route('bank-reconciliation.index') }}" class="text-muted"><i class="fa fa-arrow-left"></i></a>
                    Bank Reconciliation
                </h4>
                <div class="text-muted">
                    {{ $accountLabel }} ·
                    {{ \Carbon\Carbon::parse($reconciliation->period_from)->format('d-m-Y') }}
                    to
                    {{ \Carbon\Carbon::parse($reconciliation->period_to)->format('d-m-Y') }}
                    ·
                    @if ($isDraft)
                        <span class="badge bg-warning">Draft</span>
                    @else
                        <span class="badge bg-success">Completed</span>
                        @if ($reconciliation->completedBy)
                            by {{ $reconciliation->completedBy->name }}
                            on {{ optional($reconciliation->completed_at)->format('d-m-Y H:i') }}
                        @endif
                    @endif
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @canAccess('bank-reconciliation.print')
                    <a href="{{ route('bank-reconciliation.print', $recId) }}" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    <a href="{{ route('bank-reconciliation.pdf', $recId) }}" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                @endcanAccess
                @if ($isDraft)
                    @canAccess('bank-reconciliation.edit')
                        <button type="button" class="btn btn-outline-primary" id="btnSuggest">
                            <i class="fa fa-magic"></i> Suggest Matches
                        </button>
                    @endcanAccess
                    @canAccess('bank-reconciliation.complete')
                        <button type="button" class="btn btn-success" id="btnComplete"
                            @if (!$balances['is_balanced']) disabled @endif>
                            <i class="fa fa-check"></i> Complete
                        </button>
                    @endcanAccess
                @else
                    @canAccess('bank-reconciliation.complete')
                        <button type="button" class="btn btn-warning" id="btnReopen">
                            <i class="fa fa-undo"></i> Reopen
                        </button>
                    @endcanAccess
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Stmt Opening</label>
                        <input type="number" step="0.01" class="form-control" id="statement_opening_balance"
                            value="{{ $reconciliation->statement_opening_balance }}" @disabled(!$isDraft)>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Stmt Closing</label>
                        <input type="number" step="0.01" class="form-control" id="statement_closing_balance"
                            value="{{ $reconciliation->statement_closing_balance }}" @disabled(!$isDraft)>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Book Balance</label>
                        <div class="form-control-plaintext fw-semibold" id="lblBookBalance">
                            {{ number_format($balances['book_balance'], 2) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Adjusted Book</label>
                        <div class="form-control-plaintext fw-semibold" id="lblAdjustedBook">
                            {{ number_format($balances['adjusted_book_balance'], 2) }}
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Difference</label>
                        <div class="form-control-plaintext {{ $balances['is_balanced'] ? 'br-diff-ok' : 'br-diff-bad' }}"
                            id="lblDifference">
                            {{ number_format($balances['difference'], 2) }}
                        </div>
                    </div>
                    @if ($isDraft)
                        @canAccess('bank-reconciliation.edit')
                            <div class="col-md-2">
                                <button type="button" class="btn btn-outline-primary w-100" id="btnUpdateHeader">
                                    Update Balances
                                </button>
                            </div>
                        @endcanAccess
                    @endif
                </div>
                <div class="small text-muted mt-2" id="lblCounts">
                    Unmatched ERP: <span id="cntUnmatchedBook">{{ $balances['unmatched_book_count'] }}</span>
                    · Matched stmt: <span id="cntMatchedStmt">{{ $balances['matched_statement_count'] }}</span>
                    · Unmatched stmt: <span id="cntUnmatchedStmt">{{ $balances['unmatched_statement_count'] }}</span>
                    · Ignored: <span id="cntIgnoredStmt">{{ $balances['ignored_statement_count'] }}</span>
                </div>
                <p class="small text-muted mb-0 mt-1">
                    Difference = Statement Closing − Adjusted Book.
                    Adjusted Book removes unmatched ERP deposits and adds back unmatched ERP withdrawals
                    (including earlier uncleared items). Complete when the difference is 0.00.
                </p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong>ERP Transactions</strong>
                        <select id="bookFilter" class="form-select form-select-sm w-auto">
                            <option value="all">All</option>
                            <option value="unmatched">Unmatched</option>
                            <option value="matched">Matched</option>
                        </select>
                    </div>
                    <div class="card-body p-0 br-panel">
                        <table class="table table-sm mb-0" id="bookTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th></th>
                                    <th>Date</th>
                                    <th>Voucher</th>
                                    <th>Ref / Details</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($book_lines as $row)
                                    @php $signed = (float) $row->debit - (float) $row->credit; @endphp
                                    <tr class="book-row"
                                        data-id="{{ $row->journal_entry_detail_id }}"
                                        data-amount="{{ $signed }}"
                                        data-status="{{ $row->is_reconciled ? 'matched' : 'unmatched' }}">
                                        <td>
                                            @if ($isDraft && !$row->is_reconciled)
                                                <input type="radio" name="book_pick" value="{{ $row->journal_entry_detail_id }}"
                                                    class="book-pick">
                                            @elseif ($row->is_reconciled)
                                                <i class="fa fa-check text-success"></i>
                                            @endif
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($row->entry_date)->format('d-m-Y') }}
                                            @if (!$row->in_period)
                                                <div class="br-carry">Prior uncleared</div>
                                            @endif
                                        </td>
                                        <td>{{ $row->voucher_short ?: $row->entry_no }}</td>
                                        <td>
                                            <div>{{ $row->reference_no }}</div>
                                            <small class="text-muted">{{ $row->detail_description ?: $row->entry_description }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($signed, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted py-4">No ERP lines for this period.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <strong>Bank Statement</strong>
                        <div class="d-flex gap-2 align-items-center">
                            <select id="stmtFilter" class="form-select form-select-sm w-auto">
                                <option value="all">All</option>
                                <option value="unmatched">Unmatched</option>
                                <option value="matched">Matched</option>
                                <option value="ignored">Ignored</option>
                            </select>
                            @if ($isDraft)
                                @canAccess('bank-reconciliation.import')
                                    <a href="{{ route('bank-reconciliation.import-sample') }}" class="btn btn-sm btn-outline-secondary">Sample</a>
                                    <label class="btn btn-sm btn-outline-primary mb-0">
                                        Import
                                        <input type="file" id="importFile" accept=".csv,.xlsx,.xls" hidden>
                                    </label>
                                @endcanAccess
                                @canAccess('bank-reconciliation.edit')
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addLineModal">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" id="btnMatch" disabled>Match</button>
                                @endcanAccess
                            @endif
                        </div>
                    </div>
                    <div class="card-body p-0 br-panel">
                        <table class="table table-sm mb-0" id="stmtTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th></th>
                                    <th>Date</th>
                                    <th>Ref / Details</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($statement_lines as $line)
                                    <tr class="stmt-row"
                                        data-id="{{ $line->bank_statement_line_id }}"
                                        data-amount="{{ $line->amount }}"
                                        data-status="{{ $line->match_status }}">
                                        <td>
                                            @if ($isDraft && $line->match_status === 'unmatched')
                                                <input type="radio" name="stmt_pick" value="{{ $line->bank_statement_line_id }}"
                                                    class="stmt-pick">
                                            @elseif ($line->match_status === 'matched')
                                                <i class="fa fa-link text-success"></i>
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($line->transaction_date)->format('d-m-Y') }}</td>
                                        <td>
                                            <div>{{ $line->reference }}</div>
                                            <small class="text-muted">{{ $line->description }}</small>
                                        </td>
                                        <td class="text-end">{{ number_format($line->amount, 2) }}</td>
                                        <td>
                                            @if ($line->match_status === 'matched')
                                                <span class="badge bg-success">Matched</span>
                                            @elseif ($line->match_status === 'ignored')
                                                <span class="badge bg-secondary">Ignored</span>
                                            @else
                                                <span class="badge bg-warning">Unmatched</span>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            @if ($isDraft)
                                                @canAccess('bank-reconciliation.edit')
                                                    @if ($line->match_status === 'matched')
                                                        <button type="button" class="btn btn-xs btn-outline-warning btn-unmatch"
                                                            data-id="{{ $line->bank_statement_line_id }}">Unmatch</button>
                                                    @elseif ($line->match_status === 'unmatched')
                                                        <button type="button" class="btn btn-xs btn-outline-secondary btn-ignore"
                                                            data-id="{{ $line->bank_statement_line_id }}">Ignore</button>
                                                    @elseif ($line->match_status === 'ignored')
                                                        <button type="button" class="btn btn-xs btn-outline-primary btn-unignore"
                                                            data-id="{{ $line->bank_statement_line_id }}">Restore</button>
                                                    @endif
                                                @endcanAccess
                                                @canAccess('bank-reconciliation.edit')
                                                    @if ($line->match_status !== 'matched')
                                                        <button type="button" class="btn btn-xs btn-outline-danger btn-del-line"
                                                            data-id="{{ $line->bank_statement_line_id }}"><i class="fa fa-trash"></i></button>
                                                    @endif
                                                @endcanAccess
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted py-4">No statement lines yet. Import a CSV/Excel or add manually.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if ($isDraft)
        <div class="modal fade" id="addLineModal" tabindex="-1">
            <div class="modal-dialog">
                <form class="modal-content" id="addLineForm">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Add Statement Line') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                            <small class="text-muted">Positive = deposit/in, negative = withdrawal/out.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reference</label>
                            <input type="text" name="reference" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                        <button type="submit" class="btn btn-primary">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="suggestModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Suggested Matches</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="suggestList" class="list-group"></div>
                        <p class="text-muted small mt-2 mb-0">Suggestions are not applied automatically — confirm each match.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
@section('js')
<script>
(function () {
    const recId = @json($recId);
    const isDraft = @json($isDraft);
    const base = url_local + '/admin/bank-reconciliation/' + recId;

    function applyBalances(b) {
        if (!b) return;
        $('#lblBookBalance').text(Number(b.book_balance).toFixed(2));
        $('#lblAdjustedBook').text(Number(b.adjusted_book_balance).toFixed(2));
        const $diff = $('#lblDifference');
        $diff.text(Number(b.difference).toFixed(2));
        $diff.toggleClass('br-diff-ok', !!b.is_balanced).toggleClass('br-diff-bad', !b.is_balanced);
        $('#cntUnmatchedBook').text(b.unmatched_book_count);
        $('#cntMatchedStmt').text(b.matched_statement_count);
        $('#cntUnmatchedStmt').text(b.unmatched_statement_count);
        $('#cntIgnoredStmt').text(b.ignored_statement_count);
        $('#btnComplete').prop('disabled', !b.is_balanced);
    }

    function reloadPage() {
        window.location.reload();
    }

    function updateMatchButton() {
        $('#btnMatch').prop('disabled', !($('input.book-pick:checked').length && $('input.stmt-pick:checked').length));
    }

    $('#bookFilter').on('change', function () {
        const v = $(this).val();
        $('#bookTable tbody tr.book-row').each(function () {
            const st = $(this).data('status');
            $(this).toggle(v === 'all' || st === v);
        });
    });
    $('#stmtFilter').on('change', function () {
        const v = $(this).val();
        $('#stmtTable tbody tr.stmt-row').each(function () {
            const st = $(this).data('status');
            $(this).toggle(v === 'all' || st === v);
        });
    });

    $(document).on('change', '.book-pick, .stmt-pick', updateMatchButton);

    if (!isDraft) return;

    $('#btnUpdateHeader').on('click', function () {
        ajaxRequest({
            url: base,
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                _method: 'PUT',
                statement_opening_balance: $('#statement_opening_balance').val(),
                statement_closing_balance: $('#statement_closing_balance').val()
            }
        }).then(function (res) {
            successMessage(res.Message);
            applyBalances(res.Data.balances);
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $('#btnMatch').on('click', function () {
        const bookId = $('input.book-pick:checked').val();
        const stmtId = $('input.stmt-pick:checked').val();
        if (!bookId || !stmtId) return;
        ajaxRequest({
            url: base + '/match',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                bank_statement_line_id: stmtId,
                journal_entry_detail_id: bookId
            }
        }).then(function (res) {
            successMessage(res.Message);
            reloadPage();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $(document).on('click', '.btn-unmatch', function () {
        ajaxRequest({
            url: base + '/unmatch',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', bank_statement_line_id: $(this).data('id') }
        }).then(function (res) {
            successMessage(res.Message);
            reloadPage();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $(document).on('click', '.btn-ignore', function () {
        ajaxRequest({
            url: base + '/ignore',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', bank_statement_line_id: $(this).data('id') }
        }).then(function (res) {
            successMessage(res.Message);
            reloadPage();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $(document).on('click', '.btn-unignore', function () {
        ajaxRequest({
            url: base + '/unignore',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', bank_statement_line_id: $(this).data('id') }
        }).then(function (res) {
            successMessage(res.Message);
            reloadPage();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $(document).on('click', '.btn-del-line', function () {
        if (!confirm('Delete this statement line?')) return;
        ajaxRequest({
            url: base + '/statement-line/' + $(this).data('id'),
            method: 'POST',
            data: { _token: '{{ csrf_token() }}', _method: 'DELETE' }
        }).then(function (res) {
            successMessage(res.Message);
            reloadPage();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $('#addLineForm').on('submit', function (e) {
        e.preventDefault();
        ajaxRequest({
            url: base + '/statement-line',
            method: 'POST',
            data: $(this).serialize() + '&_token={{ csrf_token() }}'
        }).then(function (res) {
            successMessage(res.Message);
            reloadPage();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $('#importFile').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', '{{ csrf_token() }}');
        $.ajax({
            url: base + '/import',
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        }).done(function (res) {
            if (res.Success) {
                successMessage(res.Message);
                reloadPage();
            } else {
                errorMessage(res.Message || 'Import failed');
            }
        }).fail(function (xhr) {
            errorMessage((xhr.responseJSON && (xhr.responseJSON.Message || xhr.responseJSON.message)) || 'Import failed');
        });
        $(this).val('');
    });

    $('#btnSuggest').on('click', function () {
        ajaxRequest({
            url: base + '/suggest-matches',
            method: 'GET',
            data: {}
        }).then(function (res) {
            const list = (res.Data && res.Data.suggestions) || [];
            const $box = $('#suggestList').empty();
            if (!list.length) {
                $box.append('<div class="text-muted">No suggestions found.</div>');
            } else {
                list.forEach(function (s) {
                    const html = `<div class="list-group-item d-flex justify-content-between align-items-start">
                        <div>
                            <div><strong>Stmt</strong> ${s.statement.date} · ${Number(s.statement.amount).toFixed(2)} · ${s.statement.reference || ''} ${s.statement.description || ''}</div>
                            <div><strong>ERP</strong> ${s.book.date} · ${Number(s.book.amount).toFixed(2)} · ${s.book.entry_no || ''} ${s.book.reference || ''}</div>
                            <small class="text-muted">Score ${s.score}</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-success btn-apply-suggest"
                            data-stmt="${s.bank_statement_line_id}" data-book="${s.journal_entry_detail_id}">Match</button>
                    </div>`;
                    $box.append(html);
                });
            }
            new bootstrap.Modal(document.getElementById('suggestModal')).show();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $(document).on('click', '.btn-apply-suggest', function () {
        ajaxRequest({
            url: base + '/match',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                bank_statement_line_id: $(this).data('stmt'),
                journal_entry_detail_id: $(this).data('book')
            }
        }).then(function (res) {
            successMessage(res.Message);
            reloadPage();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });

    $('#btnComplete').on('click', function () {
        if (!confirm('Complete this reconciliation? It will become read-only.')) return;
        ajaxRequest({
            url: base + '/complete',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' }
        }).then(function (res) {
            successMessage(res.Message);
            reloadPage();
        }).catch(function (err) {
            errorMessage(err.Message || 'Failed');
        });
    });
})();

@if (!$isDraft)
$('#btnReopen').on('click', function () {
    if (!confirm('Reopen this reconciliation for editing?')) return;
    ajaxRequest({
        url: url_local + '/admin/bank-reconciliation/{{ $recId }}/reopen',
        method: 'POST',
        data: { _token: '{{ csrf_token() }}' }
    }).then(function (res) {
        successMessage(res.Message);
        window.location.reload();
    }).catch(function (err) {
        errorMessage(err.Message || 'Failed');
    });
});
@endif
</script>
@endsection
