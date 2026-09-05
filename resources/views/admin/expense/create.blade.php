@php
    use App\Enums\RoleNames;
    use App\Enums\Status;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($expense) ? __('expenses.update_heading') : __('expenses.new_heading') }}</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ isset($expense) ? __('expenses.update_heading') : __('expenses.create_heading') }}</h5>
                @if (isset($expense) && $expense->status === Status::POSTED)
                    <button type="button" class="btn btn-warning" id="unpostBtn" data-id="{{ $expense->expense_id }}">
                        <i class="fa fa-unlock"></i>
                        {{ __('common.unpost_to_edit') }}
                    </button>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ url('admin/expense') }}" method="POST" id="expenseForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="expense_id" value="{{ $expense->expense_id ?? '' }}">
                    <fieldset id="expenseFieldset"
                        {{ isset($expense) && $expense->status === Status::POSTED ? 'disabled' : '' }}>
                        <div class="row">
                            @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                                <div class="col-md-3 mb-3">
                                    <label>{{ __('common.business') }} <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="business_id" id="business_id">
                                        <option value="">{{ __('common.select_business') }}</option>
                                        @foreach ($business as $item)
                                            <option value="{{ $item->business_id }}"
                                                {{ old('business_id', $expense->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                                {{ $item->code }} - {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-3 mb-3">
                                <label>{{ __('expenses.expense_no') }}</label>
                                <input type="text" class="form-control" readonly
                                    value="{{ $expense->expense_no ?? ($expense_no ?? __('common.auto_generated')) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.expense_date') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker" name="expense_date"
                                    value="{{ old('expense_date', isset($expense) ? localDate($expense->expense_date) : localDate(date('Y-m-d'))) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.branch') }}</label>
                                <select class="form-control select2" name="branch_id" id="branch_id">
                                    <option value="">{{ __('expenses.not_branch_specific') }}</option>
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->branch_id }}"
                                            {{ old('branch_id', $expense->branch_id ?? '') == $item->branch_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('expenses.category') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="expense_category_id" id="expense_category_id">
                                    <option value="">{{ __('expenses.select_category') }}</option>
                                    @foreach ($categories as $item)
                                        <option value="{{ $item->expense_category_id }}"
                                            {{ old('expense_category_id', $expense->expense_category_id ?? '') == $item->expense_category_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('expenses.pos_session_optional') }}</label>
                                <select class="form-control select2" name="pos_register_session_id" id="pos_register_session_id">
                                    <option value="">{{ __('expenses.no_session_admin') }}</option>
                                    @foreach ($sessions as $item)
                                        <option value="{{ $item->pos_register_session_id }}"
                                            {{ old('pos_register_session_id', $expense->pos_register_session_id ?? '') == $item->pos_register_session_id ? 'selected' : '' }}>
                                            {{ $item->register->name ?? __('expenses.register') }} - {{ $item->cashier->name ?? '' }} -
                                            {{ localDateTime($item->opening_datetime) }} ({{ ucfirst($item->status) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('expenses.ot_user') }} {{ __('common.optional_suffix') }}</label>
                                <select class="form-control select2" name="user_id" id="user_id">
                                    <option value="">{{ __('expenses.not_assigned') }}</option>
                                    @foreach ($users as $item)
                                        <option value="{{ $item->id }}"
                                            {{ old('user_id', $expense->user_id ?? '') == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.payment_method') }} <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_method" id="payment_method">
                                    <option value="cash" {{ old('payment_method', $expense->payment_method ?? 'cash') == 'cash' ? 'selected' : '' }}>{{ __('common.cash') }}</option>
                                    <option value="bank_transfer" {{ old('payment_method', $expense->payment_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>{{ __('common.bank_transfer') }}</option>
                                    <option value="cheque" {{ old('payment_method', $expense->payment_method ?? '') == 'cheque' ? 'selected' : '' }}>{{ __('common.cheque') }}</option>
                                    <option value="online" {{ old('payment_method', $expense->payment_method ?? '') == 'online' ? 'selected' : '' }}>{{ __('common.online_payment') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3" id="payment_account_display_area">
                                <label>{{ __('common.payment_account_coa') }}</label>
                                <input type="text" class="form-control" id="payment_account_display" readonly value="--">
                            </div>
                            <div class="col-md-3 mb-3 d-none" id="payment_account_select_area">
                                <label>{{ __('common.payment_account_coa') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="payment_account_id" id="payment_account_id">
                                    <option value="">{{ __('common.select_account') }}</option>
                                    @foreach ($accounts as $item)
                                        <option value="{{ $item->account_id }}"
                                            {{ old('payment_account_id', $expense->payment_account_id ?? '') == $item->account_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.reference_no') }}</label>
                                <input type="text" class="form-control" name="reference_no"
                                    value="{{ old('reference_no', $expense->reference_no ?? '') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.amount') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="amount" name="amount"
                                    value="{{ old('amount', $expense->amount ?? 0) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('common.attachment') }}</label>
                                <input type="file" class="form-control" name="attachment">
                                @if (isset($expense) && $expense->attachment)
                                    <small>
                                        <a href="{{ asset('public/uploads/expense/' . $expense->attachment) }}" target="_blank">{{ __('common.view_current_attachment') }}</a>
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('common.description') }}</label>
                                <textarea class="form-control" rows="3" name="description">{{ old('description', $expense->description ?? '') }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button class="text-end btn btn-primary" id="saveBtn">
                                    {{ isset($expense) ? __('expenses.update_expense') : __('expenses.save_expense') }}
                                </button>
                            </div>
                        </div>
                    </fieldset>
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
        var manualPaymentAccountSelection = {{ $accounting_setting->manual_payment_account_selection ? 'true' : 'false' }};
        var defaultCashAccountId = "{{ $accounting_setting->default_cash_account_id }}";
        var defaultBankAccountId = "{{ $accounting_setting->default_bank_account_id }}";
        var accountsMap = {};
        @foreach ($accounts as $item)
            accountsMap["{{ $item->account_id }}"] = "{{ $item->code }} - {{ $item->name }}";
        @endforeach

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            togglePaymentAccountUI();
            togglePaymentMethodUI();
        });

        function togglePaymentAccountUI() {
            if (manualPaymentAccountSelection) {
                $('#payment_account_display_area').addClass('d-none');
                $('#payment_account_select_area').removeClass('d-none');
            } else {
                $('#payment_account_display_area').removeClass('d-none');
                $('#payment_account_select_area').addClass('d-none');
                updateAutoPaymentAccountDisplay();
            }
        }

        function updateAutoPaymentAccountDisplay() {
            let method = $('#payment_method').val();
            let accountId = method === 'cash' ? defaultCashAccountId : defaultBankAccountId;
            $('#payment_account_display').val(accountsMap[accountId] ?? @json(__('common.not_configured')));
        }

        function togglePaymentMethodUI() {
            if (!manualPaymentAccountSelection) {
                updateAutoPaymentAccountDisplay();
            }
        }

        $(document).on('change', '#payment_method', function() {
            togglePaymentMethodUI();
        });

        $('#expenseForm').on('submit', function(e) {
            if (!$('#expense_category_id').val()) {
                e.preventDefault();
                errorMessage(@json(__('expenses.please_select_category')));
                return false;
            }
            if (decimal($('#amount').val() || 0) <= 0) {
                e.preventDefault();
                errorMessage(@json(__('expenses.amount_gt_zero')));
                return false;
            }
            if (manualPaymentAccountSelection && !$('#payment_account_id').val()) {
                e.preventDefault();
                errorMessage(@json(__('expenses.please_select_payment_account')));
                return false;
            }
        });

        $(document).on('click', '#unpostBtn', function() {
            let expenseId = $(this).data('id');

            $.ajax({
                url: url_local + '/admin/expense/change-status',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    expense_id: expenseId,
                    status: 'pending'
                },
                success: function(response) {
                    if (response.Success === false) {
                        errorMessage(response.Message || @json(__('expenses.unable_unpost')));
                        return;
                    }
                    successMessage(response.Message);
                    location.reload();
                },
                error: function(error) {
                    errorMessage(error.responseJSON?.Message || @json(__('expenses.unable_unpost')));
                }
            });
        });
    </script>
@endsection
