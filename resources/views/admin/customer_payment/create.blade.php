@php
    use App\Enums\RoleNames;
    use App\Enums\Status;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($customer_payment) ? __('customer_payments.update_heading') : __('customer_payments.new_heading') }}</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ isset($customer_payment) ? __('customer_payments.update_heading') : __('customer_payments.create_heading') }}</h5>
                @if (isset($customer_payment) && $customer_payment->status === Status::POSTED)
                    <button type="button" class="btn btn-warning" id="unpostBtn"
                        data-id="{{ $customer_payment->customer_payment_id }}">
                        <i class="fa fa-unlock"></i>
                        {{ __('common.unpost_to_edit') }}
                    </button>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ url('admin/customer-payment') }}" method="POST" id="customerPaymentForm"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="customer_payment_id"
                        value="{{ $customer_payment->customer_payment_id ?? '' }}">
                    <fieldset id="paymentFieldset"
                        {{ isset($customer_payment) && $customer_payment->status === Status::POSTED ? 'disabled' : '' }}>
                        <div class="row">
                            @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                                <div class="col-md-3 mb-3">
                                    <label>{{ __('common.business') }} <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="business_id" id="business_id">
                                        <option value="">{{ __('common.select_business') }}</option>
                                        @foreach ($business as $item)
                                            <option value="{{ $item->business_id }}"
                                                {{ old('business_id', $customer_payment->business_id ?? ($prefill_order->business_id ?? '')) == $item->business_id ? 'selected' : '' }}>
                                                {{ $item->code }} - {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-3 mb-3">
                                <label>{{ __('customer_payments.payment_no') }}</label>
                                <input type="text" class="form-control" readonly
                                    value="{{ $customer_payment->payment_no ?? ($payment_no ?? __('common.auto_generated')) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.payment_date') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker" name="payment_date"
                                    value="{{ old('payment_date', isset($customer_payment) ? localDate($customer_payment->payment_date) : localDate(date('Y-m-d'))) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.customer') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="user_id" id="user_id">
                                    <option value="">{{ __('customer_payments.select_customer') }}</option>
                                    @foreach ($customers as $item)
                                        <option value="{{ $item->user_id }}"
                                            {{ old('user_id', $customer_payment->user_id ?? '') == $item->user_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->user->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('customer_payments.customer_balance') }}</label>
                                <input type="text" class="form-control fw-bold" id="customer_balance_display" readonly
                                    value="--">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('customer_payments.reference_order_optional') }}</label>
                                <select class="form-control select2" name="order_id" id="order_id">
                                    <option value="">{{ __('customer_payments.advance_on_account') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('customer_payments.reference_service_sale_optional') }}</label>
                                <select class="form-control select2" name="service_sale_id" id="service_sale_id">
                                    <option value="">{{ __('customer_payments.advance_on_account') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.payment_method') }} <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_method" id="payment_method">
                                    <option value="cash" {{ old('payment_method', $customer_payment->payment_method ?? 'cash') == 'cash' ? 'selected' : '' }}>{{ __('common.cash') }}</option>
                                    <option value="card" {{ old('payment_method', $customer_payment->payment_method ?? '') == 'card' ? 'selected' : '' }}>{{ __('common.card') }}</option>
                                    <option value="bank_transfer" {{ old('payment_method', $customer_payment->payment_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>{{ __('common.bank_transfer') }}</option>
                                    <option value="cheque" {{ old('payment_method', $customer_payment->payment_method ?? '') == 'cheque' ? 'selected' : '' }}>{{ __('common.cheque') }}</option>
                                    <option value="online" {{ old('payment_method', $customer_payment->payment_method ?? '') == 'online' ? 'selected' : '' }}>{{ __('common.online_payment') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3" id="payment_account_display_area">
                                <label>{{ __('common.payment_account_coa') }}</label>
                                <input type="text" class="form-control" id="payment_account_display" readonly
                                    value="--">
                            </div>
                            <div class="col-md-3 mb-3 d-none" id="payment_account_select_area">
                                <label>{{ __('common.payment_account_coa') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="payment_account_id" id="payment_account_id">
                                    <option value="">{{ __('common.select_account') }}</option>
                                    @foreach ($accounts as $item)
                                        <option value="{{ $item->account_id }}"
                                            {{ old('payment_account_id', $customer_payment->payment_account_id ?? '') == $item->account_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.reference_no') }}</label>
                                <input type="text" class="form-control" name="reference_no"
                                    value="{{ old('reference_no', $customer_payment->reference_no ?? '') }}">
                            </div>
                            <div class="col-md-3 mb-3 d-none" id="cheque_date_area">
                                <label>{{ __('customer_payments.cheque_date') }}</label>
                                <input type="text" class="form-control datepicker" name="cheque_date"
                                    value="{{ old('cheque_date', isset($customer_payment) && $customer_payment->cheque_date ? localDate($customer_payment->cheque_date) : '') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.payment_amount') }} <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="amount" name="amount"
                                    value="{{ old('amount', $customer_payment->amount ?? 0) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.tax_amount') }}</label>
                                <input type="text" class="form-control" id="tax_amount" name="tax_amount"
                                    value="{{ old('tax_amount', $customer_payment->tax_amount ?? 0) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.discount_amount') }}</label>
                                <input type="text" class="form-control" id="discount_amount" name="discount_amount"
                                    value="{{ old('discount_amount', $customer_payment->discount_amount ?? 0) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.net_payment') }}</label>
                                <input type="text" class="form-control fw-bold" id="net_amount" readonly
                                    value="{{ $customer_payment->net_amount ?? 0 }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>{{ __('common.attachment') }}</label>
                                <input type="file" class="form-control" name="attachment">
                                @if (isset($customer_payment) && $customer_payment->attachment)
                                    <small>
                                        <a href="{{ asset('public/uploads/customer_payment/' . $customer_payment->attachment) }}"
                                            target="_blank">{{ __('common.view_current_attachment') }}</a>
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>{{ __('common.remarks') }}</label>
                                <textarea class="form-control" rows="3" name="remarks">{{ old('remarks', $customer_payment->remarks ?? '') }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button class="text-end btn btn-primary" id="saveBtn">
                                    {{ isset($customer_payment) ? __('customer_payments.update_payment') : __('customer_payments.save_payment') }}
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
        var isEditMode = {{ isset($customer_payment) ? 'true' : 'false' }};
        var editCustomerId = "{{ $customer_payment->user_id ?? '' }}";
        var editOrderId = "{{ $customer_payment->order_id ?? '' }}";
        var editServiceSaleId = "{{ $customer_payment->service_sale_id ?? '' }}";
        var prefillCustomerId = "{{ $prefill_order->user_id ?? '' }}";
        var prefillOrderId = "{{ $prefill_order->order_id ?? '' }}";

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            togglePaymentAccountUI();
            togglePaymentMethodUI();

            if (isEditMode && editCustomerId) {
                loadCustomerLedger(editCustomerId);
                loadOrdersByCustomer(editCustomerId, editOrderId);
                loadServiceSalesByCustomer(editCustomerId, editServiceSaleId);
            } else if (!isEditMode && prefillCustomerId) {
                $('#user_id').val(prefillCustomerId).trigger('change');
                loadCustomerLedger(prefillCustomerId);
                loadOrdersByCustomer(prefillCustomerId, prefillOrderId);
                loadServiceSalesByCustomer(prefillCustomerId);
            }

            calculateNetPayment();
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
            $('#payment_account_display').val(accountsMap[accountId] ?? 'Not Configured');
        }

        function togglePaymentMethodUI() {
            let method = $('#payment_method').val();
            if (method === 'cheque') {
                $('#cheque_date_area').removeClass('d-none');
            } else {
                $('#cheque_date_area').addClass('d-none');
            }
            if (!manualPaymentAccountSelection) {
                updateAutoPaymentAccountDisplay();
            }
        }

        $(document).on('change', '#payment_method', function() {
            togglePaymentMethodUI();
        });

        $(document).on('change', '#user_id', function() {
            let userId = $(this).val();
            $('#order_id').html('<option value="">{{ __('customer_payments.advance_on_account') }}</option>');
            $('#service_sale_id').html('<option value="">{{ __('customer_payments.advance_on_account') }}</option>');
            if (!userId) {
                $('#customer_balance_display').val('--');
                return;
            }
            loadCustomerLedger(userId);
            loadOrdersByCustomer(userId);
            loadServiceSalesByCustomer(userId);
        });

        $(document).on('change', '#order_id', function() {
            if ($(this).val()) {
                $('#service_sale_id').val('').trigger('change.select2');
            }
        });

        $(document).on('change', '#service_sale_id', function() {
            if ($(this).val()) {
                $('#order_id').val('').trigger('change.select2');
            }
        });

        function loadCustomerLedger(userId) {
            $.ajax({
                url: url_local + '/admin/customer-payment/ledger/' + userId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#customer_balance_display').val('Loading...');
                },
                success: function(response) {
                    if (response.Success) {
                        let data = response.Data;
                        $('#customer_balance_display').val(
                            data.type ? (decimal(data.balance) + ' ' + data.type) : decimal(0)
                        );
                    }
                },
                error: function() {
                    $('#customer_balance_display').val('--');
                    errorMessage('Unable to load customer ledger balance.');
                }
            });
        }

        function loadOrdersByCustomer(userId, selectedOrderId) {
            $.ajax({
                url: url_local + '/admin/customer-payment/orders-by-customer/' + userId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let html = '<option value="">{{ __('customer_payments.advance_on_account') }}</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, order) {
                            let label = (order.daily_order_id || order.order_id) +
                                (order.order_date ? ' - ' + order.order_date : '') +
                                ' - Due: ' + decimal(order.due_amount);
                            html += `
                        <option value="${order.order_id}" data-due="${order.due_amount}">
                            ${label}
                        </option>
                    `;
                        });
                    }
                    $('#order_id').html(html);
                    if (selectedOrderId) {
                        $('#order_id').val(selectedOrderId).trigger('change');
                    }
                },
                error: function() {
                    errorMessage('Unable to load orders for this customer.');
                }
            });
        }

        function loadServiceSalesByCustomer(userId, selectedServiceSaleId) {
            $.ajax({
                url: url_local + '/admin/customer-payment/service-sales-by-customer/' + userId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let html = '<option value="">{{ __('customer_payments.advance_on_account') }}</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, serviceSale) {
                            html += `
                        <option value="${serviceSale.service_sale_id}">
                            ${serviceSale.service_sale_no} (${decimal(serviceSale.total)})
                        </option>
                    `;
                        });
                    }
                    $('#service_sale_id').html(html);
                    if (selectedServiceSaleId) {
                        $('#service_sale_id').val(selectedServiceSaleId).trigger('change');
                    }
                },
                error: function() {
                    errorMessage('Unable to load service sales for this customer.');
                }
            });
        }

        $(document).on('keyup change blur', '#amount, #tax_amount, #discount_amount', function() {
            calculateNetPayment();
        });

        function calculateNetPayment() {
            let amount = decimal($('#amount').val() || 0);
            let tax = decimal($('#tax_amount').val() || 0);
            let discount = decimal($('#discount_amount').val() || 0);
            let net = amount - tax - discount;
            $('#net_amount').val(decimal(net < 0 ? 0 : net));
        }

        $('#customerPaymentForm').on('submit', function(e) {
            if (!$('#user_id').val()) {
                e.preventDefault();
                errorMessage('Please select a customer.');
                return false;
            }
            if (decimal($('#amount').val() || 0) <= 0) {
                e.preventDefault();
                errorMessage('Payment amount must be greater than zero.');
                return false;
            }
            let orderDue = $('#order_id option:selected').data('due');
            // decimal() returns a toFixed() string - compare as numbers so
            // equal amounts (e.g. 10.61 vs 10.61) and partials are not
            // rejected by lexicographic string comparison.
            if ($('#order_id').val() && orderDue !== undefined && parseFloat(decimal($('#amount').val() || 0)) >
                parseFloat(decimal(orderDue))) {
                e.preventDefault();
                errorMessage('Payment amount exceeds the selected order\'s remaining due.');
                return false;
            }
            if (manualPaymentAccountSelection && !$('#payment_account_id').val()) {
                e.preventDefault();
                errorMessage('Please select a payment account.');
                return false;
            }
            let net = decimal($('#amount').val() || 0) - decimal($('#tax_amount').val() || 0) - decimal($(
                '#discount_amount').val() || 0);
            if (net < 0) {
                e.preventDefault();
                errorMessage('Tax and discount amount cannot exceed the payment amount.');
                return false;
            }
        });

        $(document).on('click', '#unpostBtn', function() {
            let customerPaymentId = $(this).data('id');

            $.ajax({
                url: url_local + '/admin/customer-payment/change-status',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    customer_payment_id: customerPaymentId,
                    status: 'pending'
                },
                success: function(response) {
                    successMessage(response.Message);
                    location.reload();
                },
                error: function(error) {
                    errorMessage(error.responseJSON?.Message || 'Unable to unpost this payment.');
                }
            });
        });
    </script>
@endsection
