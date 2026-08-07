@php
    use App\Enums\RoleNames;
    use App\Enums\Status;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($supplier_payment) ? 'Update' : 'New' }} Supplier Payment</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ isset($supplier_payment) ? 'Update' : 'Create' }} Supplier Payment</h5>
                @if (isset($supplier_payment) && $supplier_payment->status === Status::POSTED)
                    <button type="button" class="btn btn-warning" id="unpostBtn"
                        data-id="{{ $supplier_payment->supplier_payment_id }}">
                        <i class="fa fa-unlock"></i>
                        Unpost to Edit
                    </button>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ url('admin/supplier-payment') }}" method="POST" id="supplierPaymentForm"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="supplier_payment_id"
                        value="{{ $supplier_payment->supplier_payment_id ?? '' }}">
                    <fieldset id="paymentFieldset"
                        {{ isset($supplier_payment) && $supplier_payment->status === Status::POSTED ? 'disabled' : '' }}>
                        <div class="row">
                            @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                                <div class="col-md-3 mb-3">
                                    <label>Business <span class="text-danger">*</span></label>
                                    <select class="form-control select2" name="business_id" id="business_id">
                                        <option value="">--Select Business--</option>
                                        @foreach ($business as $item)
                                            <option value="{{ $item->business_id }}"
                                                {{ old('business_id', $supplier_payment->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                                {{ $item->code }} - {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-md-3 mb-3">
                                <label>Payment No.</label>
                                <input type="text" class="form-control" readonly
                                    value="{{ $supplier_payment->payment_no ?? ($payment_no ?? 'Auto Generated') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Payment Date <span class="text-danger">*</span></label>
                                <input type="text" class="form-control datepicker" name="payment_date"
                                    value="{{ old('payment_date', isset($supplier_payment) ? localDate($supplier_payment->payment_date) : localDate(date('Y-m-d'))) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Supplier <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="supplier_id" id="supplier_id">
                                    <option value="">--Select Supplier--</option>
                                    @foreach ($suppliers as $item)
                                        <option value="{{ $item->supplier_id }}"
                                            {{ old('supplier_id', $supplier_payment->supplier_id ?? '') == $item->supplier_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Supplier Balance</label>
                                <input type="text" class="form-control fw-bold" id="supplier_balance_display" readonly
                                    value="--">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Supplier COA</label>
                                <input type="text" class="form-control" id="supplier_coa_display" readonly
                                    value="--">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Reference Purchase (Optional)</label>
                                <select class="form-control select2" name="purchase_id" id="purchase_id">
                                    <option value="">--Advance / Not Linked--</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_method" id="payment_method">
                                    <option value="cash" {{ old('payment_method', $supplier_payment->payment_method ?? 'cash') == 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="bank_transfer" {{ old('payment_method', $supplier_payment->payment_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="cheque" {{ old('payment_method', $supplier_payment->payment_method ?? '') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                                    <option value="online" {{ old('payment_method', $supplier_payment->payment_method ?? '') == 'online' ? 'selected' : '' }}>Online Payment</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3" id="payment_account_display_area">
                                <label>Payment Account COA</label>
                                <input type="text" class="form-control" id="payment_account_display" readonly
                                    value="--">
                            </div>
                            <div class="col-md-3 mb-3 d-none" id="payment_account_select_area">
                                <label>Payment Account COA <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="payment_account_id" id="payment_account_id">
                                    <option value="">--Select Account--</option>
                                    @foreach ($accounts as $item)
                                        <option value="{{ $item->account_id }}"
                                            {{ old('payment_account_id', $supplier_payment->payment_account_id ?? '') == $item->account_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Reference No.</label>
                                <input type="text" class="form-control" name="reference_no"
                                    value="{{ old('reference_no', $supplier_payment->reference_no ?? '') }}">
                            </div>
                            <div class="col-md-3 mb-3 d-none" id="cheque_date_area">
                                <label>Cheque Date</label>
                                <input type="text" class="form-control datepicker" name="cheque_date"
                                    value="{{ old('cheque_date', isset($supplier_payment) && $supplier_payment->cheque_date ? localDate($supplier_payment->cheque_date) : '') }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Payment Amount <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="amount" name="amount"
                                    value="{{ old('amount', $supplier_payment->amount ?? 0) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Tax Amount (Withholding)</label>
                                <input type="text" class="form-control" id="tax_amount" name="tax_amount"
                                    value="{{ old('tax_amount', $supplier_payment->tax_amount ?? 0) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Discount Amount</label>
                                <input type="text" class="form-control" id="discount_amount" name="discount_amount"
                                    value="{{ old('discount_amount', $supplier_payment->discount_amount ?? 0) }}">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>Net Payment</label>
                                <input type="text" class="form-control fw-bold" id="net_amount" readonly
                                    value="{{ $supplier_payment->net_amount ?? 0 }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Attachment</label>
                                <input type="file" class="form-control" name="attachment">
                                @if (isset($supplier_payment) && $supplier_payment->attachment)
                                    <small>
                                        <a href="{{ asset('public/uploads/supplier_payment/' . $supplier_payment->attachment) }}"
                                            target="_blank">View current attachment</a>
                                    </small>
                                @endif
                            </div>
                            <div class="col-md-12 mb-3">
                                <label>Remarks</label>
                                <textarea class="form-control" rows="3" name="remarks">{{ old('remarks', $supplier_payment->remarks ?? '') }}</textarea>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button class="text-end btn btn-primary" id="saveBtn">
                                    {{ isset($supplier_payment) ? 'Update Payment' : 'Save Payment' }}
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
        var isEditMode = {{ isset($supplier_payment) ? 'true' : 'false' }};
        var editSupplierId = "{{ $supplier_payment->supplier_id ?? '' }}";
        var editPurchaseId = "{{ $supplier_payment->purchase_id ?? '' }}";

        // ======================================================
        // DOCUMENT READY
        // ======================================================
        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            togglePaymentAccountUI();
            togglePaymentMethodUI();

            if (isEditMode && editSupplierId) {
                loadSupplierLedger(editSupplierId);
                loadPurchasesBySupplier(editSupplierId, editPurchaseId);
            }

            calculateNetPayment();
        });

        // ======================================================
        // PAYMENT ACCOUNT UI (auto vs manual)
        // ======================================================

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

        // ======================================================
        // PAYMENT METHOD CHANGE
        // ======================================================

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

        // ======================================================
        // SUPPLIER CHANGE -> LEDGER + PURCHASES
        // ======================================================

        $(document).on('change', '#supplier_id', function() {
            let supplierId = $(this).val();
            $('#purchase_id').html('<option value="">--Advance / Not Linked--</option>');
            if (!supplierId) {
                $('#supplier_balance_display').val('--');
                $('#supplier_coa_display').val('--');
                return;
            }
            loadSupplierLedger(supplierId);
            loadPurchasesBySupplier(supplierId);
        });

        function loadSupplierLedger(supplierId) {
            $.ajax({
                url: url_local + '/admin/supplier-payment/ledger/' + supplierId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#supplier_balance_display').val('Loading...');
                    $('#supplier_coa_display').val('Loading...');
                },
                success: function(response) {
                    if (response.Success) {
                        let data = response.Data;
                        $('#supplier_balance_display').val(
                            data.type ? (decimal(data.balance) + ' ' + data.type) : decimal(0)
                        );
                        $('#supplier_coa_display').val(
                            data.account_code || data.account_name ?
                            (data.account_code + ' - ' + data.account_name) :
                            'Not Configured'
                        );
                    }
                },
                error: function() {
                    $('#supplier_balance_display').val('--');
                    $('#supplier_coa_display').val('--');
                    errorMessage('Unable to load supplier ledger balance.');
                }
            });
        }

        function loadPurchasesBySupplier(supplierId, selectedPurchaseId) {
            $.ajax({
                url: url_local + '/admin/supplier-payment/purchases-by-supplier/' + supplierId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let html = '<option value="">--Advance / Not Linked--</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, purchase) {
                            html += `
                        <option value="${purchase.purchase_id}">
                            ${purchase.purchase_no} (${currencyFormat(purchase.total)})
                        </option>
                    `;
                        });
                    }
                    $('#purchase_id').html(html);
                    if (selectedPurchaseId) {
                        $('#purchase_id').val(selectedPurchaseId).trigger('change');
                    }
                },
                error: function() {
                    errorMessage('Unable to load purchases for this supplier.');
                }
            });
        }

        function currencyFormat(amount) {
            return decimal(amount);
        }

        // ======================================================
        // NET PAYMENT CALCULATION
        // ======================================================

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

        // ======================================================
        // FORM SUBMIT VALIDATION
        // ======================================================

        $('#supplierPaymentForm').on('submit', function(e) {
            if (!$('#supplier_id').val()) {
                e.preventDefault();
                errorMessage('Please select a supplier.');
                return false;
            }
            if (decimal($('#amount').val() || 0) <= 0) {
                e.preventDefault();
                errorMessage('Payment amount must be greater than zero.');
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

        // ======================================================
        // UNPOST
        // ======================================================

        $(document).on('click', '#unpostBtn', function() {
            let supplierPaymentId = $(this).data('id');

            $.ajax({
                url: url_local + '/admin/supplier-payment/change-status',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    supplier_payment_id: supplierPaymentId,
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
