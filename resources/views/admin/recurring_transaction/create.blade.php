@php
    use App\Enums\RoleNames;
    use App\Enums\RecurringTransactionType;
    use App\Enums\RecurringFrequency;
    $template = $rt->template_data ?? [];
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($rt) ? 'Update' : 'New' }} Recurring Transaction</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($rt) ? 'Update' : 'Create' }} Recurring Transaction</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/recurring-transaction') }}" method="POST" id="recurringTransactionForm">
                    @csrf
                    <input type="hidden" name="recurring_transaction_id" value="{{ $rt->recurring_transaction_id ?? '' }}">

                    <h6 class="mb-3">Schedule</h6>
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $rt->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>Branch</label>
                            <select class="form-control select2" name="branch_id" id="branch_id">
                                <option value="">--Not Branch Specific--</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}"
                                        {{ old('branch_id', $rt->branch_id ?? '') == $item->branch_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Schedule Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $rt->name ?? '') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Transaction Type <span class="text-danger">*</span></label>
                            <select class="form-control" name="transaction_type" id="transaction_type" {{ isset($rt) ? 'disabled' : '' }}>
                                <option value="">--Select Type--</option>
                                @foreach ($types as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('transaction_type', $rt->transaction_type ?? '') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($rt))
                                <input type="hidden" name="transaction_type" value="{{ $rt->transaction_type }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Frequency <span class="text-danger">*</span></label>
                            <select class="form-control" name="frequency" id="frequency">
                                <option value="">--Select Frequency--</option>
                                @foreach (RecurringFrequency::all() as $item)
                                    <option value="{{ $item }}"
                                        {{ old('frequency', $rt->frequency ?? '') == $item ? 'selected' : '' }}>
                                        {{ ucfirst($item) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 freq-field" data-freq="weekly">
                            <label>Day of Week</label>
                            <select class="form-control" name="weekday" id="weekday">
                                @foreach (['0' => 'Sunday', '1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('weekday', $rt->weekday ?? '') === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3 freq-field" data-freq="monthly,yearly">
                            <label>Day of Month</label>
                            <input type="number" min="1" max="31" class="form-control" name="day_of_month" id="day_of_month"
                                value="{{ old('day_of_month', $rt->day_of_month ?? '') }}">
                        </div>
                        <div class="col-md-3 mb-3 freq-field" data-freq="yearly">
                            <label>Month</label>
                            <select class="form-control" name="month_of_year" id="month_of_year">
                                @foreach (['1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April', '5' => 'May', '6' => 'June', '7' => 'July', '8' => 'August', '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'] as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ old('month_of_year', $rt->month_of_year ?? '') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="start_date"
                                value="{{ old('start_date', isset($rt) ? localDate($rt->start_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>End Date (Optional)</label>
                            <input type="text" class="form-control datepicker" name="end_date"
                                value="{{ old('end_date', isset($rt) && $rt->end_date ? localDate($rt->end_date) : '') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Max Occurrences (Optional)</label>
                            <input type="number" min="1" class="form-control" name="max_occurrences"
                                value="{{ old('max_occurrences', $rt->max_occurrences ?? '') }}">
                        </div>
                        <div class="col-md-3 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="auto_post" id="auto_post" value="1"
                                    {{ old('auto_post', $rt->auto_post ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_post">
                                    Auto-post generated transactions
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="previewNextRunBtn">
                                <i class="fa fa-calendar"></i> Preview Next Runs
                            </button>
                            <span id="previewNextRunResult" class="ms-2"></span>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Notes</label>
                            <textarea class="form-control" rows="2" name="notes">{{ old('notes', $rt->notes ?? '') }}</textarea>
                        </div>
                    </div>

                    <hr>

                    <div id="expenseSection">
                        <h6 class="mb-3">Expense Details</h6>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>Expense Category <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="expense_category_id">
                                    <option value="">--Select Category--</option>
                                    @foreach ($categories as $item)
                                        <option value="{{ $item->expense_category_id }}"
                                            {{ old('expense_category_id', $template['expense_category_id'] ?? '') == $item->expense_category_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" name="expense_payment_method">
                                    @foreach (['cash' => 'Cash', 'bank_transfer' => 'Bank Transfer', 'cheque' => 'Cheque', 'online' => 'Online Payment'] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('expense_payment_method', $template['payment_method'] ?? 'cash') == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Payment Account COA</label>
                                <select class="form-control select2" name="expense_payment_account_id">
                                    <option value="">--Auto / Default--</option>
                                    @foreach ($accounts as $item)
                                        <option value="{{ $item->account_id }}"
                                            {{ old('expense_payment_account_id', $template['payment_account_id'] ?? '') == $item->account_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Reference No.</label>
                                <input type="text" class="form-control" name="expense_reference_no"
                                    value="{{ old('expense_reference_no', $template['reference_no'] ?? '') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Amount <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="expense_amount"
                                    value="{{ old('expense_amount', $template['amount'] ?? '') }}">
                            </div>
                            <div class="col-md-9 mb-3">
                                <label>Description</label>
                                <textarea class="form-control" rows="2" name="expense_description">{{ old('expense_description', $template['description'] ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div id="journalEntrySection">
                        <h6 class="mb-3">Journal Entry Details</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Journal <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="je_journal_id" id="je_journal_id">
                                    <option value="">--Select Journal--</option>
                                    @foreach ($journals as $item)
                                        <option value="{{ $item->journal_id }}"
                                            {{ old('je_journal_id', $template['journal_id'] ?? '') == $item->journal_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Reference No.</label>
                                <input type="text" class="form-control" name="je_reference_no"
                                    value="{{ old('je_reference_no', $template['reference_no'] ?? '') }}">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>Description</label>
                                <input type="text" class="form-control" name="je_description"
                                    value="{{ old('je_description', $template['description'] ?? '') }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Account</label>
                                <select id="je_line_account_id" class="form-control select2">
                                    <option value="">--Select Account--</option>
                                    @foreach ($accounts as $item)
                                        <option value="{{ $item->account_id }}" data-label="{{ $item->code }} - {{ $item->name }}">
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label>Debit</label>
                                <input type="number" step="0.01" class="form-control" id="je_line_debit">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label>Credit</label>
                                <input type="number" step="0.01" class="form-control" id="je_line_credit">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Description</label>
                                <input type="text" class="form-control" id="je_line_description">
                            </div>
                            <div class="col-md-1 mb-3 d-grid align-self-end">
                                <button type="button" class="btn btn-primary" id="je_add_line">Add</button>
                            </div>
                        </div>
                        <table class="table table-bordered" id="je_lines_table">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th>Debit</th>
                                    <th>Credit</th>
                                    <th>Description</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th id="je_total_debit">0.00</th>
                                    <th id="je_total_credit">0.00</th>
                                    <th colspan="2"></th>
                                </tr>
                            </tfoot>
                        </table>
                        <input type="hidden" name="je_lines" id="je_lines">
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <button class="text-end btn btn-primary" id="saveBtn">
                                {{ isset($rt) ? 'Update Schedule' : 'Save Schedule' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @if ($errors->any())
        <script>
            errorMessage("{{ $errors->first() }}");
        </script>
    @endif
    @if (session('error'))
        <script>
            errorMessage("{{ session('error') }}");
        </script>
    @endif
    <script>
        var existingLines = {!! json_encode($template['lines'] ?? []) !!};
        var accountLabels = {};
        @foreach ($accounts as $item)
            accountLabels["{{ $item->account_id }}"] = "{{ $item->code }} - {{ $item->name }}";
        @endforeach

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            toggleTransactionTypeUI();
            toggleFrequencyUI();

            existingLines.forEach(function(line) {
                addJeLineRow(line.account_id, accountLabels[line.account_id] || line.account_id, line.debit, line.credit, line.description);
            });
            recalculateJeTotals();
        });

        function toggleTransactionTypeUI() {
            var type = $('#transaction_type').val() || $('input[name="transaction_type"]').val();
            $('#expenseSection').toggle(type === 'expense');
            $('#journalEntrySection').toggle(type === 'journal_entry');
        }

        function toggleFrequencyUI() {
            var freq = $('#frequency').val();
            $('.freq-field').each(function() {
                var allowed = $(this).data('freq').toString().split(',');
                $(this).toggle(allowed.includes(freq));
            });
        }

        $('#transaction_type').on('change', toggleTransactionTypeUI);
        $('#frequency').on('change', toggleFrequencyUI);

        function addJeLineRow(accountId, accountLabel, debit, credit, description) {
            var row = $('<tr>');
            row.append('<td class="je-account" data-id="' + accountId + '">' + accountLabel + '</td>');
            row.append('<td class="je-debit">' + parseFloat(debit || 0).toFixed(2) + '</td>');
            row.append('<td class="je-credit">' + parseFloat(credit || 0).toFixed(2) + '</td>');
            row.append('<td class="je-description">' + (description || '') + '</td>');
            row.append('<td><button type="button" class="btn btn-sm btn-outline-danger je_remove_line"><i class="fa fa-trash"></i></button></td>');
            $('#je_lines_table tbody').append(row);
        }

        $('#je_add_line').on('click', function() {
            var accountId = $('#je_line_account_id').val();
            var accountLabel = $('#je_line_account_id option:selected').text();
            var debit = parseFloat($('#je_line_debit').val() || 0);
            var credit = parseFloat($('#je_line_credit').val() || 0);
            var description = $('#je_line_description').val();

            if (!accountId) {
                errorMessage('Please select an account.');
                return;
            }
            if ((debit === 0 && credit === 0) || (debit > 0 && credit > 0)) {
                errorMessage('Enter either a Debit or a Credit amount (not both, not neither).');
                return;
            }

            addJeLineRow(accountId, accountLabel, debit, credit, description);
            recalculateJeTotals();

            $('#je_line_account_id').val('').trigger('change');
            $('#je_line_debit').val('');
            $('#je_line_credit').val('');
            $('#je_line_description').val('');
        });

        $(document).on('click', '.je_remove_line', function() {
            $(this).closest('tr').remove();
            recalculateJeTotals();
        });

        function recalculateJeTotals() {
            var totalDebit = 0,
                totalCredit = 0;
            $('#je_lines_table tbody tr').each(function() {
                totalDebit += parseFloat($(this).find('.je-debit').text() || 0);
                totalCredit += parseFloat($(this).find('.je-credit').text() || 0);
            });
            $('#je_total_debit').text(totalDebit.toFixed(2));
            $('#je_total_credit').text(totalCredit.toFixed(2));
        }

        $('#previewNextRunBtn').on('click', function() {
            $.ajax({
                url: url_local + '/admin/recurring-transaction/preview-next-run',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    frequency: $('#frequency').val(),
                    weekday: $('#weekday').val(),
                    day_of_month: $('#day_of_month').val(),
                    month_of_year: $('#month_of_year').val(),
                    start_date: $('input[name="start_date"]').val() ? toIsoDate($('input[name="start_date"]').val()) : '',
                    end_date: $('input[name="end_date"]').val() ? toIsoDate($('input[name="end_date"]').val()) : '',
                },
                success: function(response) {
                    if (response.Success === false) {
                        errorMessage(response.Message || 'Unable to preview.');
                        return;
                    }
                    $('#previewNextRunResult').text((response.Data || []).join(', '));
                },
                error: function(error) {
                    errorMessage(error.responseJSON?.Message || 'Unable to preview.');
                }
            });
        });

        function toIsoDate(value) {
            // datepicker fields render using the business's configured date
            // format (see localDate()) - the preview endpoint expects Y-m-d.
            var parts = value.split(/[\/\-]/);
            if (parts.length === 3 && parts[0].length === 4) {
                return value;
            }
            if (parts.length === 3) {
                return parts[2] + '-' + parts[1] + '-' + parts[0];
            }
            return value;
        }

        $('#recurringTransactionForm').on('submit', function(e) {
            var type = $('#transaction_type').val() || $('input[name="transaction_type"]').val();

            if (type === 'journal_entry') {
                var lines = [];
                $('#je_lines_table tbody tr').each(function() {
                    lines.push({
                        account_id: $(this).find('.je-account').data('id'),
                        debit: parseFloat($(this).find('.je-debit').text() || 0),
                        credit: parseFloat($(this).find('.je-credit').text() || 0),
                        description: $(this).find('.je-description').text(),
                    });
                });

                if (lines.length < 2) {
                    e.preventDefault();
                    errorMessage('Please add at least 2 journal entry lines.');
                    return false;
                }

                $('#je_lines').val(JSON.stringify(lines));
            }
        });
    </script>
@endsection
