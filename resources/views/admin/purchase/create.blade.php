@php
    use App\Enums\RoleNames;
    use Carbon\Carbon;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($purchase) ? __('common.update') : __('common.new') }} {{ __('purchases.singular') }}</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($purchase) ? __('common.update') : __('common.create') }} {{ __('purchases.singular') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/purchase') }}" method="POST" id="purchaseForm">
                    @csrf
                    <input type="hidden" name="purchase_id" value="{{ $purchase->purchase_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.business') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">{{ __('common.select_business') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $purchase->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>
                                {{ __('purchases.purchase_type') }}<span class="text-danger">*</span>
                            </label>
                            <select class="form-control" name="purchase_type" id="purchase_type"
                                {{ isset($purchase) ? 'disabled' : '' }}>
                                <option value="direct"
                                    {{ old('purchase_type', $purchase->purchase_type ?? 'direct') == 'direct' ? 'selected' : '' }}>
                                    {{ __('purchases.direct_purchase') }}
                                </option>
                                <option value="purchase_request"
                                    {{ old('purchase_type', $purchase->purchase_type ?? '') == 'purchase_request' ? 'selected' : '' }}>
                                    {{ __('purchases.from_purchase_request') }}
                                </option>
                            </select>
                            @if (isset($purchase))
                                <input type="hidden" name="purchase_type" value="{{ $purchase->purchase_type }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3 purchase-request-area">
                            <label>
                                {{ __('purchases.purchase_request') }}<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="purchase_request_id" id="purchase_request_id"
                                {{ isset($purchase) && $purchase->purchase_type == 'purchase_request' ? 'disabled' : '' }}>
                                <option value="">{{ __('purchases.select_purchase_request') }}</option>
                                @foreach ($purchase_requests as $item)
                                    <option value="{{ $item->purchase_request_id }}"
                                        {{ old('purchase_request_id', $purchase->purchase_request_id ?? '') == $item->purchase_request_id ? 'selected' : '' }}>
                                        {{ $item->purchase_request_no }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($purchase) && $purchase->purchase_type == 'purchase_request')
                                <input type="hidden" name="purchase_request_id"
                                    value="{{ $purchase->purchase_request_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3 purchase-request-area">
                            <label>
                                {{ __('purchases.quotation') }}<span class="text-danger">*</span>
                            </label>
                            <select class="form-control" id="purchase_request_quotation_id"
                                {{ isset($purchase) && $purchase->purchase_type == 'purchase_request' ? 'disabled' : '' }}>
                                <option value="">{{ __('purchases.select_quotation') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="mb-0">
                                    {{ __('common.supplier') }}<span class="text-danger">*</span>
                                </label>
                                @include('admin.partials.quick-add-btn', ['permission' => 'supplier.create', 'modal' => 'quickAddSupplierModal', 'label' => 'Supplier'])
                            </div>
                            <select class="form-control select2" name="supplier_id" id="supplier_id">
                                <option value="">{{ __('common.select_supplier') }}</option>
                                @foreach ($suppliers as $item)
                                    <option value="{{ $item->supplier_id }}"
                                        {{ old('supplier_id', $purchase->supplier_id ?? '') == $item->supplier_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>
                                {{ __('common.warehouse') }}<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="warehouse_id" id="warehouse_id">
                                <option value="">{{ __('common.select_warehouse') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}"
                                        {{ old('warehouse_id', $purchase->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('purchases.po_number') }}</label>
                            <input type="text" class="form-control" name="purchase_no" readonly
                                value="{{ $purchase->purchase_no ?? ($purchase_no ?? '{{ __('common.auto_generated') }}') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('purchases.purchase_date') }}</label>
                            <input type="text" class="form-control datepicker" name="purchase_date"
                                value="{{ old('purchase_date', isset($purchase) ? localDate($purchase->purchase_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('purchases.expected_delivery_date') }}</label>
                            <input type="text" class="form-control datepicker" name="expected_delivery_date"
                                value="{{ old('expected_delivery_date', isset($purchase) ? localDate($purchase->expected_delivery_date) : localDate(date('Y-m-d', strtotime('+7 days')))) }}">
                        </div>
                        <div class="col-md-12">
                            <label>{{ __('common.description') }}</label>
                            <textarea class="form-control" rows="3" name="description">{{ old('description', $purchase->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <hr>
                    {{-- ================= PRODUCT TABLE ================= --}}
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">
                                {{ __('common.products') }}
                            </h5>
                            <div class="d-flex align-items-center gap-2">
                                <div class="input-group" style="width:260px;">
                                    <input type="text" class="form-control" id="purchase_barcode_scan"
                                        placeholder="{{ __('common.scan_barcode_placeholder') }}">
                                    @include('admin.partials.barcode_scanner', ['targetInputId' => '#purchase_barcode_scan'])
                                </div>
                                <button type="button" class="btn btn-primary" id="addProductBtn">
                                    <i class="fa fa-plus"></i>{{ __('common.add_product') }}
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="productTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px;">{{ __('common.product') }}</th>
                                        <th style="min-width:180px;">{{ __('common.variation') }}</th>
                                        <th style="min-width:170px;">{{ __('common.conversion') }}</th>
                                        <th style="min-width:90px;">{{ __('common.unit') }}</th>
                                        <th style="min-width:120px;">{{ __('common.ordered_qty') }}</th>
                                        <th style="min-width:120px;">{{ __('common.received_qty') }}</th>
                                        <th style="min-width:130px;">{{ __('common.batch_no') }}</th>
                                        <th style="min-width:150px;">{{ __('common.expiry_date') }}</th>
                                        <th style="min-width:160px;">{{ __('common.serial_number') }}</th>
                                        <th style="min-width:120px;">{{ __('common.unit_price') }}</th>
                                        <th style="min-width:130px;">{{ __('common.subtotal') }}</th>
                                        <th style="min-width:90px;">{{ __('common.discount_percent') }}</th>
                                        <th style="min-width:130px;">{{ __('common.disc_amount') }}</th>
                                        <th style="min-width:90px;">{{ __('common.tax_percent') }}</th>
                                        <th style="min-width:130px;">{{ __('common.tax_amount') }}</th>
                                        <th style="min-width:130px">{{ __('common.total') }}</th>
                                        <th style="min-width:60px;">{{ __('common.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    {{-- JS will append rows --}}
                                    @if (!isset($purchase))
                                        <tr id="emptyRow">
                                            <td colspan="17" class="text-center text-muted">
                                                {{ __('common.no_products_added') }}
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <br>
                    {{-- ================= FOOTER TOTALS ================= --}}
                    <div class="row">
                        <div class="offset-md-8 col-md-4">
                            <table class="table table-bordered">
                                <tr>
                                    <th>{{ __('common.subtotal') }}</th>
                                    <td>
                                        <input class="form-control" id="subtotal" name="subtotal" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('common.discount_amount') }}</th>
                                    <td>
                                        <input class="form-control" id="discount_amount" name="discount_amount" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('common.tax_amount') }}</th>
                                    <td>
                                        <input class="form-control" id="tax_amount" name="tax_amount" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('common.shipping') }}</th>
                                    <td>
                                        <input class="form-control" id="shipping_charge" name="shipping_charge"
                                            value="{{ old('shipping_charge', $purchase->shipping_charge ?? 0) }}">
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('common.total') }}</th>
                                    <td>
                                        <input class="form-control fw-bold" id="total" name="total" readonly>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="text-end btn btn-primary">
                                {{ isset($purchase) ? __('purchases.update_purchase') : __('purchases.save_purchase') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('admin.supplier.model.quick-create', ['business' => $business ?? []])

    {{-- ================= SERIAL NUMBER ENTRY MODAL ================= --}}
    <div class="modal fade" id="serialEntryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('purchases.enter_serial_numbers') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2" id="serialModalHint">{{ __('purchases.one_serial_per_line') }}</p>
                    <textarea class="form-control" id="serialModalTextarea" rows="8"
                        placeholder="{{ __('purchases.serial_placeholder') }}"></textarea>
                    <div class="mt-2">
                        <input type="text" class="form-control d-none" id="serialScanHelperInput">
                        @include('admin.partials.barcode_scanner', ['targetInputId' => '#serialScanHelperInput'])
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="serialModalSaveBtn">{{ __('common.save') }}</button>
                </div>
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
        // ======================================================
        // GLOBAL VARIABLES
        // ======================================================
        var productIndex = 0;
        var purchaseType = 'direct';
        var productsData = [];
        var isEditMode = {{ isset($purchase) ? 'true' : 'false' }};
        var editPurchaseData = @json($purchase_details ?? null);
        window.i18n_purchases = {
            loading: @json(__('common.loading')),
            na: @json(__('common.na')),
            batch_no_placeholder: @json(__('purchases.batch_no_placeholder')),
            expiry_date_placeholder: @json(__('purchases.expiry_date_placeholder')),
            enter_serials: @json(__('purchases.enter_serials')),
            select_purchase_request_hint: @json(__('purchases.select_purchase_request_hint')),
        };
        // ======================================================
        // DOCUMENT READY
        // ======================================================
        $(function() {
            initializePage();
            bindPurchaseType();
            initializeExistingRows();

            initQuickAdd({
                modalId: '#quickAddSupplierModal',
                formId: '#quickAddSupplierForm',
                url: url_local + '/admin/supplier',
                valueField: 'supplier_id',
                labelField: 'name',
                targetSelectIds: ['supplier_id'],
            });
        });

        // ======================================================
        // INITIALIZE PAGE
        // ======================================================
        function initializePage() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }
            productIndex = $('#productRows tr.product-row').length;
            purchaseType = $('#purchase_type').val();
            togglePurchaseTypeUI();
            if (isEditMode) {
                initializeUpdateMode();
            } else {
                initializeCreateMode();
            }
        }

        // ======================================================
        // CREATE MODE
        // ======================================================

        function initializeCreateMode() {
            if (purchaseType == 'direct') {
                if ($('#productRows tr.product-row').length == 0) {
                    addProductRow();
                }
            } else {
                if (!$('#purchase_request_id').val()) {
                    $('#productRows').html(`
                <tr id="emptyRow">
                    <td colspan="17"
                        class="text-center text-muted">
                        Select {{ __('purchases.purchase_request') }}
                    </td>
                </tr>
            `);
                }
            }
        }

        // ======================================================
        // UPDATE MODE
        // ======================================================

        function initializeUpdateMode() {
            $('#purchase_type').prop('disabled', true);
            if (purchaseType == 'purchase_request') {
                $('#purchase_request_id').prop('disabled', true);
            }
            loadPurchaseForEdit();
        }
        // ======================================================
        // PURCHASE TYPE
        // ======================================================
        function bindPurchaseType() {
            $(document).on('change', '#purchase_type', function() {
                purchaseType = $(this).val();
                resetPurchaseForm();
                togglePurchaseTypeUI();
            });

        }

        // ======================================================
        // RESET FORM ON PURCHASE TYPE CHANGE
        // ======================================================

        function resetPurchaseForm() {
            productIndex = 0;

            // Clear {{ __('purchases.purchase_request') }} -> cascades to clear {{ __('purchases.quotation') }},
            // remove all product rows and reset subtotal/discount/tax
            $('#purchase_request_id').val(null).trigger('change');

            // Reset shipping charge and refresh the grand total
            $('#shipping_charge').val(decimal(0));
            applyFooterCalculations();
        }

        // ======================================================
        // SHOW/HIDE UI
        // ======================================================

        function togglePurchaseTypeUI() {
            if (purchaseType == 'direct') {
                showDirectPurchase();
            } else {
                showPurchaseRequest();
            }
        }
        // ======================================================
        // DIRECT PURCHASE
        // ======================================================

        function showDirectPurchase() {
            $('.purchase-request-area').hide();
            $('#addProductBtn').show();
            // #purchase_type is always disabled in edit mode, so this reset-on-toggle
            // only makes sense for a live user-driven type change, never on initial
            // page load (where a direct purchase may still carry a stale request id).
            if (!isEditMode && $('#purchase_request_id').val()) {
                $('#purchase_request_id').val(null).trigger('change');
            }
            if ($('#productRows tr.product-row').length == 0) {
                $('#productRows').empty();
                addProductRow();
            }
        }

        // ======================================================
        // PURCHASE REQUEST
        // ======================================================

        function showPurchaseRequest() {
            $('.purchase-request-area').show();
            $('#addProductBtn').hide();
            if (!$('#purchase_request_id').val()) {
                $('#productRows').html(`
            <tr id="emptyRow">
                <td colspan="17"
                    class="text-center text-muted">
                    Select {{ __('purchases.purchase_request') }}
                </td>
            </tr>
        `);
            }
        }

        // ======================================================
        // COMMON PRODUCT ROW TEMPLATE
        // ======================================================
        $(document).off('click', '#addProductBtn').on('click', '#addProductBtn', function() {
            addProductRow();
        });

        function getRowTemplate(data = {}) {
            const index = productIndex;
            const serialNumbers = data.serial_numbers || [];
            const serialInputsHtml = serialNumbers.map(sn =>
                `<input type="hidden" class="serial-hidden-input" name="products[${index}][serial_numbers][]" value="${sn}">`
            ).join('');
            return `
    <tr class="product-row">

        <td>
            <input type="hidden"
            name="products[${index}][purchase_request_detail_id]"
            value="${data.purchase_request_detail_id ?? ''}">
            <select
                name="products[${index}][product_id]"
                class="form-control product-select">

                <option value="">{{ __('common.select_product') }}</option>

            </select>
        </td>
        <td>
            <select
                name="products[${index}][product_variation_id]"
                class="form-control variation-select">
                <option value="">{{ __('common.select_variation') }}</option>
            </select>
        </td>
        <td>
            <select
                name="products[${index}][product_variation_unit_conversion_id]"
                class="form-control conversion-select">
                <option value="">{{ __('common.select_conversion') }}</option>
            </select>
            <input
                type="hidden"
                class="conversion-factor"
                name="products[${index}][conversion_factor]"
                value="${data.conversion_factor ?? 1}">

        </td>
        <td>
            <input
                type="hidden"
                class="selected-unit-id"
                name="products[${index}][unit_id]"
                value="${data.unit_id ?? ''}">
            <span class="selected-unit-name">
                ${data.unit_name ?? '-'}
            </span>
        </td>
        <td>
            <input
                type="hidden"
                class="quoted-qty"
                value="${data.quoted_quantity ?? data.ordered_quantity ?? 0}">
            <input
                type="text"
                class="form-control ordered-qty"
                name="products[${index}][ordered_quantity]"
                value="${data.ordered_quantity ?? 0}">

        </td>
        <td>
            <input
                type="text"
                class="form-control received-qty"
                name="products[${index}][received_quantity]"
                value="${data.received_quantity ?? data.ordered_quantity ?? 0}"
                readonly>
        </td>
        <td class="batch-cell">
            <input type="text" class="form-control batch-no" name="products[${index}][batch_no]"
                value="${data.batch_no ?? ''}" placeholder="${(window.i18n_purchases && window.i18n_purchases.batch_no_placeholder) || 'Batch No.'}" style="display:none;">
            <span class="batch-no-na text-muted">${(window.i18n_purchases && window.i18n_purchases.na) || 'N/A'}</span>
        </td>
        <td class="expiry-cell">
            <input type="text" class="form-control datepicker expiry-date" name="products[${index}][expiry_date]"
                value="${data.expiry_date ?? ''}" placeholder="${(window.i18n_purchases && window.i18n_purchases.expiry_date_placeholder) || 'Expiry Date'}" style="display:none;">
            <span class="expiry-date-na text-muted">${(window.i18n_purchases && window.i18n_purchases.na) || 'N/A'}</span>
        </td>
        <td class="serial-cell">
            <button type="button" class="btn btn-sm btn-outline-primary serial-entry-btn" style="display:none;">
                <i class="fa fa-barcode"></i> <span class="serial-count-label">${(window.i18n_purchases && window.i18n_purchases.enter_serials) || 'Enter Serials'} (${serialNumbers.length}/0)</span>
            </button>
            <span class="serial-na text-muted">${(window.i18n_purchases && window.i18n_purchases.na) || 'N/A'}</span>
            <div class="serial-hidden-inputs" style="display:none;">${serialInputsHtml}</div>
        </td>
        <td>
            <input
                type="text"
                class="form-control unit-price"
                name="products[${index}][unit_price]"
                value="${data.unit_price ?? 0}">
        </td>
        <td>
            <input
                type="text"
                class="form-control row-subtotal"
                name="products[${index}][subtotal]"
                value="${data.subtotal ?? 0}" readonly>
        </td>
        <td>
            <input
                type="text"
                class="form-control row-discount"
                name="products[${index}][discount]"
                value="${data.discount ?? 0}">
        </td>
        <td>
            <input
                type="text"
                class="form-control row-discount-amount"
                name="products[${index}][discount_amount]"
                value="${data.discount_amount ?? 0}" readonly>
        </td>
        <td>
            <input
                type="text"
                class="form-control row-tax"
                name="products[${index}][tax]"
                value="${data.tax ?? 0}">
        </td>
        <td>
            <input
                type="text"
                class="form-control row-tax-amount"
                name="products[${index}][tax_amount]"
                value="${data.tax_amount ?? 0}" readonly>
        </td>
        <td>
            <input
                type="text"
                class="form-control row-total"
                readonly
                name="products[${index}][total]"
                value="${data.total ?? 0}">
        </td>
        <td class="text-center">
            <button
                type="button"
                class="btn btn-sm btn-danger remove-row">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
`;
        }

        // ======================================================
        // ADD PRODUCT ROW
        // ======================================================

        function addProductRow(data = {}) {
            $('#emptyRow').remove();
            $('#productRows').append(getRowTemplate(data));
            loadProducts(productIndex);
            productIndex++;
        }

        // ======================================================
        // REMOVE PRODUCT ROW
        // ======================================================

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            if ($('#productRows tr.product-row').length == 0) {
                if (purchaseType == 'direct') {
                    addProductRow();
                } else {
                    $('#productRows').html(`
            <tr id="emptyRow">
                <td colspan="17"
                    class="text-center text-muted">
                    Select {{ __('purchases.purchase_request') }}
                </td>
            </tr>
        `);

                }
            }
        });

        // ======================================================
        // LOAD PRODUCT DROPDOWN
        // ======================================================

        var productsData = @json($products);

        function loadProducts(index) {
            let html = `<option value="">{{ __('common.select_product') }}</option>`;
            $.each(productsData, function(_, product) {
                html += `
        <option value="${product.product_id}">
            ${product.name}
        </option>
    `;
            });

            $('#productRows tr:last')
                .find('.product-select')
                .html(html);
        }

        // ======================================================
        // BUSINESS CHANGE
        // ======================================================

        $(document).on('change', '#business_id', function() {
            let businessId = $(this).val();
            resetBusinessData();
            if (!businessId) {
                return;
            }
            loadSuppliers(businessId);
            loadWarehouses(businessId);
            loadProductsByBusiness(businessId);
        });

        // ======================================================
        // RESET BUSINESS DATA
        // ======================================================

        function resetBusinessData() {
            $('#supplier_id').html('<option value="">{{ __('common.select_supplier') }}</option>');
            $('#warehouse_id').html('<option value="">{{ __('common.select_warehouse') }}</option>');
            productsData = [];
            $('.product-select').each(function() {
                $(this).html('<option value="">{{ __('common.select_product') }}</option>');
            });
        }

        // ======================================================
        // LOAD SUPPLIERS
        // ======================================================

        function loadSuppliers(businessId) {
            $.ajax({
                url: url_local + '/admin/supplier/by-business/' + businessId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#supplier_id')
                        .prop('disabled', true)
                        .html('<option>' + ((window.i18n_purchases && window.i18n_purchases.loading) || 'Loading...') + '</option>');
                },
                success: function(response) {
                    let html = '<option value="">{{ __('common.select_supplier') }}</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, supplier) {
                            html += `
                    <option value="${supplier.supplier_id}">
                        ${supplier.code ?? ''} ${supplier.name}
                    </option>
                `;
                        });
                    }
                    $('#supplier_id')
                        .html(html)
                        .prop('disabled', false);
                },
                error: function() {
                    $('#supplier_id')
                        .html('<option value="">{{ __('common.select_supplier') }}</option>')
                        .prop('disabled', false);
                    errorMessage('Unable to load suppliers.');
                }
            });
        }

        // ======================================================
        // LOAD WAREHOUSES
        // ======================================================

        function loadWarehouses(businessId) {
            $.ajax({
                url: url_local + '/admin/warehouse/by-business/' + businessId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#warehouse_id')
                        .prop('disabled', true)
                        .html('<option>' + ((window.i18n_purchases && window.i18n_purchases.loading) || 'Loading...') + '</option>');
                },
                success: function(response) {
                    let html = '<option value="">{{ __('common.select_warehouse') }}</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, warehouse) {
                            html += `
                    <option value="${warehouse.warehouse_id}">
                        ${warehouse.name}
                    </option>
                `;
                        });
                    }
                    $('#warehouse_id')
                        .html(html)
                        .prop('disabled', false);
                },
                error: function() {
                    $('#warehouse_id')
                        .html('<option value="">{{ __('common.select_warehouse') }}</option>')
                        .prop('disabled', false);
                    errorMessage('Unable to load warehouses.');
                }
            });
        }

        // ======================================================
        // LOAD PRODUCTS
        // ======================================================
        function loadProductsByBusiness(businessId) {
            $.ajax({
                url: url_local + '/admin/product/by-business/' + businessId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    productsData = [];
                },
                success: function(response) {
                    if (response.Success) {
                        productsData = response.Data;
                        refreshProductDropdowns();
                    }
                },
                error: function() {
                    errorMessage('Unable to load products.');
                }
            });
        }

        // ======================================================
        // REFRESH PRODUCT DROPDOWNS
        // ======================================================

        function refreshProductDropdowns() {
            let options = `<option value="">{{ __('common.select_product') }}</option>`;
            $.each(productsData, function(_, product) {
                options += `
        <option value="${product.product_id}">
            ${product.name}
        </option>
    `;
            });

            $('.product-select').each(function() {
                let currentValue = $(this).val();
                $(this).html(options);
                if (currentValue) {
                    $(this).val(currentValue);
                }
            });
        }

        // ======================================================
        // BARCODE SCAN -> ADD/SELECT PRODUCT ROW
        // ======================================================

        $('#purchase_barcode_scan').on('change keypress', function(e) {
            if (e.type === 'keypress' && e.which !== 13) {
                return;
            }
            e.preventDefault();

            let code = $(this).val().trim();
            if (!code) {
                return;
            }

            resolveBarcodeLookup(code, function(data) {
                addScannedProductToPurchase(data.product, data.variation);
                $('#purchase_barcode_scan').val('').focus();
            });
        });

        function addScannedProductToPurchase(product, variation) {
            let existingRow = null;

            $('.product-row').each(function() {
                let row = $(this);
                if (
                    row.find('.product-select').val() === product.product_id &&
                    row.find('.variation-select').val() === variation.product_variation_id
                ) {
                    existingRow = row;
                    return false;
                }
            });

            if (existingRow) {
                let qtyInput = existingRow.find('.ordered-qty');
                qtyInput.val((parseFloat(qtyInput.val()) || 0) + 1).trigger('change');
                return;
            }

            addProductRow();
            let row = $('.product-row').last();

            row.find('.product-select').val(product.product_id);
            resetVariationSection(row);

            loadVariations(product.product_id, row, function(row) {
                row.find('.variation-select').val(variation.product_variation_id).trigger('change');
            });
        }

        // ======================================================
        // PRODUCT CHANGE -> LOAD VARIATIONS
        // ======================================================

        $(document).on('change', '.product-select', function() {
            let row = $(this).closest('tr');
            let productId = $(this).val();
            resetVariationSection(row);
            if (!productId) {
                return;
            }
            loadVariations(productId, row);
        });

        // ======================================================
        // RESET VARIATION SECTION
        // ======================================================

        function resetVariationSection(row) {
            row.find('.variation-select').html(`
    <option value="">{{ __('common.select_variation') }}</option>
`);
            row.find('.conversion-select').html(`
    <option value="">{{ __('common.select_conversion') }}</option>
`);
            row.find('.selected-unit-id').val('');
            row.find('.selected-unit-name').html('-');
            row.find('.conversion-factor').val(1);
            row.find('.unit-price').val(0);
        }

        // ======================================================
        // LOAD VARIATIONS
        // ======================================================

        function loadVariations(productId, row, onLoaded) {
            $.ajax({
                url: url_local + '/admin/product/variation-by-product/' + productId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    row.find('.variation-select')
                        .html('<option>' + ((window.i18n_purchases && window.i18n_purchases.loading) || 'Loading...') + '</option>');
                },
                success: function(response) {
                    let html = '<option value="">{{ __('common.select_variation') }}</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, variation) {
                            html += `
                    <option
                        value="${variation.product_variation_id}"
                        data-unit-id="${variation.purchase_unit?.unit_id ?? ''}"
                        data-unit-name="${variation.purchase_unit?.name ?? ''}"
                        data-price="${decimal(variation.purchase_price ?? 0)}"
                        data-track-batch="${variation.track_batch ? 1 : 0}"
                        data-track-expiry="${variation.track_expiry ? 1 : 0}"
                        data-track-serial="${variation.track_serial_number ? 1 : 0}"
                    >
                        ${variation.name}

                    </option>
                `;

                        });
                    }
                    row.find('.variation-select').html(html);
                    if (typeof onLoaded === 'function') {
                        onLoaded(row);
                    }
                },
                error: function() {
                    errorMessage('Unable to load variations.');
                }

            });

        }

        // ======================================================
        // VARIATION CHANGE
        // ======================================================

        $(document).on('change', '.variation-select', function() {

            let row = $(this).closest('tr');

            let variationId = $(this).val();

            let option = $(this).find(':selected');

            row.find('.selected-unit-id').val(option.data('unit-id'));

            row.find('.selected-unit-name').html(option.data('unit-name'));

            row.find('.unit-price').val(decimal(option.data('price')));

            row.find('.conversion-factor').val(1);

            setBatchExpiryState(row, option.data('track-batch') == 1, option.data('track-expiry') == 1);
            setSerialState(row, option.data('track-serial') == 1);

            row.find('.conversion-select').html(`
    <option value="">${(window.i18n_purchases && window.i18n_purchases.loading) || 'Loading...'}</option>
`);

            if (!variationId) {
                return;
            }

            loadConversions(variationId, row);

        });

        // The batch/expiry <td>s always stay in the row so every row keeps
        // the same number of cells as the header (hiding a <td> itself would
        // shift the remaining cells in that row into the wrong columns).
        // Only the input inside is toggled, with a muted "N/A" shown instead.
        function setBatchExpiryState(row, showBatch, showExpiry) {
            if (showBatch) {
                row.find('.batch-no').show();
                row.find('.batch-no-na').hide();
            } else {
                row.find('.batch-no').val('').hide();
                row.find('.batch-no-na').show();
            }

            if (showExpiry) {
                row.find('.expiry-date').show();
                row.find('.expiry-date-na').hide();
                let expiryInput = row.find('.expiry-date').get(0);
                if (expiryInput && typeof flatpickr === 'function' && !expiryInput._flatpickr) {
                    flatpickr(expiryInput, {
                        dateFormat: "{{ session('business_setting.date_format', 'd-m-Y') }}",
                        allowInput: true
                    });
                }
            } else {
                row.find('.expiry-date').val('').hide();
                row.find('.expiry-date-na').show();
            }
        }

        // Same show/hide-in-place treatment as batch/expiry. Turning serial
        // tracking off clears any already-entered serials for the row.
        function setSerialState(row, showSerial) {
            row.data('track-serial', !!showSerial);
            if (showSerial) {
                row.find('.serial-entry-btn').show();
                row.find('.serial-na').hide();
            } else {
                row.find('.serial-hidden-inputs').empty();
                row.find('.serial-entry-btn').hide();
                row.find('.serial-na').show();
            }
            refreshSerialButtonLabel(row);
        }

        function refreshSerialButtonLabel(row) {
            const entered = row.find('.serial-hidden-input').length;
            const expected = decimal(row.find('.received-qty').val()) || 0;
            row.find('.serial-count-label').text(`${(window.i18n_purchases && window.i18n_purchases.enter_serials) || 'Enter Serials'} (${entered}/${expected})`);
            row.find('.serial-entry-btn').toggleClass('btn-outline-primary', entered == expected)
                .toggleClass('btn-outline-danger', entered != expected);
        }

        // Refresh the "X/Y" denominator whenever Received Qty changes.
        $(document).on('change blur', '.received-qty', function() {
            refreshSerialButtonLabel($(this).closest('tr'));
        });

        // ======================================================
        // SERIAL NUMBER ENTRY MODAL
        // ======================================================
        var serialEntryModal = null;
        var currentSerialRow = null;

        $(document).on('click', '.serial-entry-btn', function() {
            currentSerialRow = $(this).closest('tr');
            const existing = currentSerialRow.find('.serial-hidden-input').map(function() {
                return $(this).val();
            }).get();
            $('#serialModalTextarea').val(existing.join('\n'));
            const expected = decimal(currentSerialRow.find('.received-qty').val()) || 0;
            $('#serialModalHint').text(`Enter exactly ${expected} serial number(s), one per line, matching the received quantity.`);
            serialEntryModal = serialEntryModal || new bootstrap.Modal(document.getElementById('serialEntryModal'));
            serialEntryModal.show();
        });

        // A scan inside the serial modal appends the decoded code as a new
        // line rather than replacing the textarea (barcode-scanner.js's
        // default single-input replace behavior, reused unmodified).
        $(document).on('change', '#serialScanHelperInput', function() {
            const code = $(this).val();
            if (!code) {
                return;
            }
            const ta = $('#serialModalTextarea');
            const current = ta.val();
            ta.val(current && !current.endsWith('\n') ? current + '\n' + code : current + code);
            $(this).val('');
        });

        $('#serialModalSaveBtn').on('click', function() {
            if (!currentSerialRow) {
                return;
            }
            const lines = $('#serialModalTextarea').val()
                .split('\n')
                .map(s => s.trim())
                .filter(s => s !== '');

            const duplicates = lines.filter((v, i) => lines.indexOf(v) !== i);
            if (duplicates.length) {
                errorMessage('Duplicate serial number(s) entered: ' + [...new Set(duplicates)].join(', '));
                return;
            }

            const index = currentSerialRow.find('.product-select').attr('name').match(/products\[(\d+)\]/)[1];
            currentSerialRow.find('.serial-hidden-inputs').html(
                lines.map(sn => `<input type="hidden" class="serial-hidden-input" name="products[${index}][serial_numbers][]" value="${sn.replace(/"/g, '&quot;')}">`).join('')
            );

            refreshSerialButtonLabel(currentSerialRow);
            bootstrap.Modal.getInstance(document.getElementById('serialEntryModal')).hide();
        });

        // ======================================================
        // LOAD CONVERSIONS
        // ======================================================

        function loadConversions(variationId, row) {

            $.ajax({

                url: url_local + '/admin/product-variation-unit-conversion/by-variation/' + variationId,

                type: 'GET',

                dataType: 'json',

                success: function(response) {

                    let html = `
            <option value="">
                {{ __('common.select_conversion') }}
            </option>
        `;

                    if (response.Success && response.Data.length) {

                        $.each(response.Data, function(_, conversion) {

                            html += `
                    <option

                        value="${conversion.product_variation_unit_conversion_id}"

                        data-factor="${conversion.conversion_factor}"

                        data-unit-id="${conversion.to_unit_id}"

                        data-unit-name="${conversion.to_unit?.name}"

                    >

                        ${conversion.from_unit?.name}
                        →

                        ${conversion.to_unit?.name}

                        (${conversion.conversion_factor})

                    </option>
                `;

                        });

                    }

                    row.find('.conversion-select').html(html);

                },

                error: function() {

                    errorMessage('Unable to load conversions.');

                }

            });

        }

        // ======================================================
        // CONVERSION CHANGE
        // ======================================================

        $(document).on('change', '.conversion-select', function() {

            let row = $(this).closest('tr');

            let option = $(this).find(':selected');

            row.find('.conversion-factor').val(

                option.data('factor') || 1

            );

            row.find('.selected-unit-id').val(

                option.data('unit-id') || ''

            );

            row.find('.selected-unit-name').html(

                option.data('unit-name') || '-'

            );

            calculateRow(row);

        });

        // ======================================================
        // PRICE / QTY CHANGE
        // ======================================================

        $(document).on(
            'keyup change',
            '.ordered-qty,.received-qty,.unit-price,.row-discount,.row-tax',
            function() {
                let row = $(this).closest('tr');
                if (
                    purchaseType == 'direct' &&
                    $(this).hasClass('ordered-qty')
                ) {
                    row.find('.received-qty')
                        .val($(this).val());
                }
                calculateRow(row);
            }
        );

        // ======================================================
        // PURCHASE REQUEST CHANGE
        // ======================================================

        $(document).on('change', '#purchase_request_id', function() {
            let purchase_request_id = $(this).val();
            reset{{ __('purchases.quotation') }}Select();
            showSelectPurchaseRequestRow();
            $('#shipping_charge').val(decimal(0));
            calculateGrandTotal();
            if (!purchase_request_id) {
                return;
            }
            load{{ __('purchases.quotation') }}sByPurchaseRequest(purchase_request_id);

        });

        // ======================================================
        // RESET QUOTATION SELECT
        // ======================================================

        function reset{{ __('purchases.quotation') }}Select() {
            $('#purchase_request_quotation_id')
                .html('<option value="">--Select {{ __('purchases.quotation') }}--</option>');
        }

        // ======================================================
        // PLACEHOLDER ROWS
        // ======================================================

        function showSelectPurchaseRequestRow() {
            $('#productRows').html(`
        <tr id="emptyRow">
            <td colspan="17" class="text-center text-muted">
                Select {{ __('purchases.purchase_request') }}
            </td>
        </tr>
    `);
        }

        function showSelect{{ __('purchases.quotation') }}Row() {
            $('#productRows').html(`
        <tr id="emptyRow">
            <td colspan="17" class="text-center text-muted">
                Select {{ __('purchases.quotation') }}
            </td>
        </tr>
    `);
        }

        // ======================================================
        // LOAD QUOTATIONS FOR PURCHASE REQUEST
        // ======================================================

        function load{{ __('purchases.quotation') }}sByPurchaseRequest(purchase_request_id) {
            $.ajax({
                url: url_local +
                    '/admin/purchase-request-quotation/selected-by-purchase-request/' +
                    purchase_request_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#purchase_request_quotation_id')
                        .prop('disabled', true)
                        .html('<option>' + ((window.i18n_purchases && window.i18n_purchases.loading) || 'Loading...') + '</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select {{ __('purchases.quotation') }}--</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, quotation) {
                            html += `
                    <option value="${quotation.purchase_request_quotation_id}">
                        ${quotation.purchase_request_quotation_no} - ${quotation.supplier?.name ?? ''}
                    </option>
                `;
                        });
                    } else {
                        $('#productRows').html(`
            <tr>
                <td colspan="17" class="text-center">
                    No approved quotation found for this purchase request.
                </td>
            </tr>
        `);
                    }
                    $('#purchase_request_quotation_id')
                        .html(html)
                        .prop('disabled', false);
                    if (response.Success && response.Data.length) {
                        showSelect{{ __('purchases.quotation') }}Row();
                    }
                },
                error: function() {
                    $('#purchase_request_quotation_id')
                        .html('<option value="">--Select {{ __('purchases.quotation') }}--</option>')
                        .prop('disabled', false);
                    errorMessage('Unable to load quotations.');
                }
            });
        }

        // ======================================================
        // QUOTATION CHANGE
        // ======================================================

        $(document).on('change', '#purchase_request_quotation_id', function() {
            let purchase_request_quotation_id = $(this).val();
            if (!purchase_request_quotation_id) {
                showSelect{{ __('purchases.quotation') }}Row();
                calculateGrandTotal();
                return;
            }
            loadPurchaseRequest{{ __('purchases.quotation') }}Details(purchase_request_quotation_id);
        });

        // ======================================================
        // LOAD SELECTED QUOTATION DETAILS
        // ======================================================

        function loadPurchaseRequest{{ __('purchases.quotation') }}Details(purchase_request_quotation_id) {
            $.ajax({
                url: url_local +
                    '/admin/purchase-request-quotation/details/' +
                    purchase_request_quotation_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#productRows').html(`
            <tr>
                <td colspan="17" class="text-center">
                    <div class="spinner-border spinner-border-sm"></div>
                    ${(window.i18n_purchases && window.i18n_purchases.loading) || 'Loading...'}
                </td>
            </tr>
        `);

                },

                success: function(response) {
                    if (!response.Success) {
                        errorMessage(response.Message);
                        return;
                    }
                    bindPurchaseRequestHeader(response.Data.header);
                    bindPurchaseRequestProducts(response.Data.details);
                },
                error: function() {
                    errorMessage('Unable to load quotation.');
                }

            });

        }

        // ======================================================
        // HEADER
        // ======================================================
        function bindPurchaseRequestHeader(header) {
            if (!header)
                return;
            if (header.supplier_id) {
                $('#supplier_id')
                    .val(header.supplier_id)
                    .trigger('change');
            }
            if (header.description) {

                $('textarea[name="description"]')
                    .val(header.description);
            }

            // {{ __('purchases.quotation') }}'s Other Charges populates Shipping Charges,
            // staying editable afterwards.
            $('#shipping_charge').val(decimal(header.other_charge ?? 0));
        }

        // ======================================================
        // DETAILS
        // ======================================================

        function bindPurchaseRequestProducts(details) {
            $('#productRows').html('');
            productIndex = 0;
            if (!details.length) {
                $('#productRows').html(`
        <tr>
            <td colspan="17"
                class="text-center text-muted">
                No Products Found

            </td>
        </tr>
    `);

                calculateGrandTotal();
                return;
            }
            $.each(details, function(_, item) {
                addPurchaseRequestRow(item);
            });
            calculateGrandTotal();
        }

        // ======================================================
        // PURCHASE REQUEST ROW
        // ======================================================

        function addPurchaseRequestRow(item) {
            addProductRow({
                product_id: item.product_id,
                product_variation_id: item.product_variation_id,
                purchase_request_detail_id: item.purchase_request_detail_id,
                ordered_quantity: decimal(item.ordered_quantity),
                quoted_quantity: decimal(item.quoted_quantity ?? item.ordered_quantity),
                received_quantity: decimal(0),
                unit_price: decimal(item.unit_price),
                subtotal: decimal(item.subtotal),
                discount: decimal(item.discount),
                discount_amount: decimal(item.discount_amount),
                tax: decimal(item.tax),
                tax_amount: decimal(item.tax_amount),
                total: decimal(item.total),
                unit_id: item.unit_id,
                unit_name: item.unit_name,
                conversion_factor: item.conversion_factor,
                product_name: item.product_name,
                product_variation_name: item.product_variation_name
            });
            let row = $('#productRows tr:last');
            // Product
            row.find('.product-select').html(`
                <option value="${item.product_id}" selected>
                    ${item.product_name}
                </option>
            `);
            // Disabled selects are not posted by the browser, so add a
            // hidden field carrying the same name to keep the value submitted.
            row.find('.product-select')
                .prop('disabled', true)
                .after(`<input type="hidden" name="${row.find('.product-select').attr('name')}" value="${item.product_id}">`);
            // Variation
            row.find('.variation-select').html(`
            <option value="${item.product_variation_id}" selected>
                ${item.product_variation_name}
            </option>
            `);
            row.find('.variation-select')
                .prop('disabled', true)
                .after(`<input type="hidden" name="${row.find('.variation-select').attr('name')}" value="${item.product_variation_id}">`);
            // Conversion
            let conversionOptions =
                `<option value="">{{ __('common.select_conversion') }}</option>`;
            if (item.conversions) {
                $.each(item.conversions, function(_, conversion) {
                    conversionOptions += `
            <option
                value="${conversion.product_variation_unit_conversion_id}"
                data-factor="${conversion.conversion_factor}"
                data-unit-id="${conversion.to_unit_id}"
                data-unit-name="${conversion.to_unit_name}"
            >
                ${conversion.from_unit_name}
                →
                ${conversion.to_unit_name}
                (${conversion.conversion_factor})
            </option>
        `;
                });
            }
            row.find('.conversion-select')
                .html(conversionOptions);
            // Conversion is intentionally left unselected here so the
            // quotation's original values display untouched; picking a
            // conversion afterwards triggers recalculation (see the
            // .conversion-select change handler).
            row.find('.selected-unit-id')
                .val(item.unit_id);
            row.find('.selected-unit-name')
                .html(item.unit_name);
            row.find('.conversion-factor')
                .val(item.conversion_factor ?? 1);
            row.find('.ordered-qty')
                .val(decimal(item.ordered_quantity))
                .prop('readonly', true);
            row.find('.received-qty')
                .val(0)
                .prop('readonly', true);

            // Display the quotation's stored calculations as-is; do not
            // recompute them until the user changes something (e.g. conversion).
            syncRowTotals(row);

        }

        // ======================================================
        // UPDATE PAGE
        // ======================================================

        function loadPurchaseForEdit() {

            if (!editPurchaseData || !editPurchaseData.details || !editPurchaseData.details.length) {
                return;
            }

            $('#productRows').html('');

            productIndex = 0;

            $.each(editPurchaseData.details, function(_, item) {
                if (purchaseType == 'purchase_request') {
                    // Same locked-row treatment already used for quotation-driven rows.
                    addPurchaseRequestRow(item);
                } else {
                    addDirectEditProductRow(item);
                }
            });

            calculateGrandTotal();

        }

        // ======================================================
        // DIRECT PURCHASE EDIT ROW (product/variation stay editable)
        // ======================================================

        function addDirectEditProductRow(item) {
            addProductRow({
                ordered_quantity: decimal(item.ordered_quantity),
                received_quantity: decimal(item.received_quantity ?? item.ordered_quantity),
                unit_price: decimal(item.unit_price),
                subtotal: decimal(item.subtotal),
                discount: decimal(item.discount),
                discount_amount: decimal(item.discount_amount),
                tax: decimal(item.tax),
                tax_amount: decimal(item.tax_amount),
                total: decimal(item.total),
                unit_id: item.unit_id,
                unit_name: item.unit_name,
                conversion_factor: item.conversion_factor,
                serial_numbers: item.serial_numbers || []
            });

            let row = $('#productRows tr:last');
            row.find('.product-select').val(item.product_id);

            loadVariations(item.product_id, row, function() {
                if (row.find('.variation-select option[value="' + item.product_variation_id + '"]').length === 0) {
                    row.find('.variation-select')
                        .append(`<option value="${item.product_variation_id}">${item.product_variation_name}</option>`);
                }
                row.find('.variation-select').val(item.product_variation_id);

                let selectedOption = row.find('.variation-select option[value="' + item.product_variation_id + '"]');
                setBatchExpiryState(row, selectedOption.data('track-batch') == 1, selectedOption.data('track-expiry') == 1);
                setSerialState(row, (selectedOption.data('track-serial') == 1) || item.track_serial_number);

                // Conversions are already supplied by getDetails(); no extra AJAX call needed.
                let conversionOptions = `<option value="">{{ __('common.select_conversion') }}</option>`;
                $.each(item.conversions || [], function(_, conversion) {
                    conversionOptions += `
                        <option
                            value="${conversion.product_variation_unit_conversion_id}"
                            data-factor="${conversion.conversion_factor}"
                            data-unit-id="${conversion.to_unit_id}"
                            data-unit-name="${conversion.to_unit_name}">
                            ${conversion.from_unit_name} → ${conversion.to_unit_name} (${conversion.conversion_factor})
                        </option>
                    `;
                });
                row.find('.conversion-select').html(conversionOptions);
                if (item.product_variation_unit_conversion_id) {
                    row.find('.conversion-select').val(item.product_variation_unit_conversion_id);
                }

                row.find('.selected-unit-id').val(item.unit_id);
                row.find('.selected-unit-name').html(item.unit_name);
                row.find('.conversion-factor').val(item.conversion_factor ?? 1);

                // Display the stored calculations as-is; existing change handlers
                // recalculate once the user edits anything on the row.
                syncRowTotals(row);
            });
        }

        // ======================================================
        // SYNC ROW TOTALS (no recalculation)
        // ======================================================

        // Caches the row's currently displayed subtotal/discount/tax/total
        // into its .data() so calculateGrandTotal() can sum it, without
        // recomputing the values themselves.
        function syncRowTotals(row) {
            row.data('subtotal', decimal(row.find('.row-subtotal').val()));
            row.data('discount_amount', decimal(row.find('.row-discount-amount').val()));
            row.data('tax_amount', decimal(row.find('.row-tax-amount').val()));
            row.data('total', decimal(row.find('.row-total').val()));
        }

        // ======================================================
        // ROW CALCULATION
        // ======================================================

        function calculateRow(row) {
            let orderedQty = decimal(
                row.find('.ordered-qty').val()
            );
            let receivedQty = decimal(
                row.find('.received-qty').val()
            );
            let unitPrice = decimal(
                row.find('.unit-price').val()
            );
            let discountPercent = decimal(
                row.find('.row-discount').val()
            );
            let taxPercent = decimal(
                row.find('.row-tax').val()
            );

            // Received cannot exceed Ordered

            if (receivedQty > orderedQty) {
                receivedQty = orderedQty;
                row.find('.received-qty').val(orderedQty);

            }

            //------------------------------------------------

            let conversionFactor = decimal(
                row.find('.conversion-factor').val()
            );
            if (conversionFactor <= 0) {
                conversionFactor = 1;
            }
            let calculationQty = orderedQty * conversionFactor;
            let subtotal = calculationQty * unitPrice;
            let discountAmount =
                subtotal * discountPercent / 100;
            let afterDiscount =
                subtotal - discountAmount;
            let taxAmount =
                afterDiscount * taxPercent / 100;
            let total =
                afterDiscount + taxAmount;
            //------------------------------------------------

            row.data('subtotal', decimal(subtotal));
            row.data('discount_amount', decimal(discountAmount));
            row.data('tax_amount', decimal(taxAmount));
            row.data('total', decimal(total));

            //------------------------------------------------
            row.find('.row-subtotal')
                .val(decimal(subtotal));
            row.find('.row-discount-amount')
                .val(decimal(discountAmount));
            row.find('.row-tax-amount')
                .val(decimal(taxAmount));
            row.find('.row-total')
                .val(decimal(total));

            calculateGrandTotal();

        }

        // ======================================================
        // GRAND TOTAL
        // ======================================================

        function calculateGrandTotal() {

            let subtotal = 0;
            let discount = 0;
            let tax = 0;
            let grandTotal = 0;

            $('#productRows tr.product-row').each(function() {

                subtotal += decimal(
                    $(this).data('subtotal')
                );

                discount += decimal(
                    $(this).data('discount_amount')
                );

                tax += decimal(
                    $(this).data('tax_amount')
                );

                grandTotal += decimal(
                    $(this).data('total')
                );

            });

            //------------------------------------------------

            $('#subtotal')
                .val(decimal(subtotal));

            $('#discount_amount')
                .val(decimal(discount));

            $('#tax_amount')
                .val(decimal(tax));

            //------------------------------------------------

            applyFooterCalculations();

        }

        // ======================================================
        // FOOTER CALCULATION
        // ======================================================

        function applyFooterCalculations() {

            let subtotal = decimal($('#subtotal').val() || 0);
            let discount = decimal($('#discount_amount').val() || 0);
            let tax = decimal($('#tax_amount').val() || 0);
            let shipping = decimal($('#shipping_charge').val() || 0);
            let total = subtotal - discount + (tax*1 + shipping*1);
            $('#total').val(decimal(total));
        }

        // ======================================================
        // FOOTER EVENTS
        // ======================================================

        $(document).off('change', '#shipping_charge')
            .on('change', '#shipping_charge', function() {
                applyFooterCalculations();
            });

        // ======================================================
        // RECALCULATE ALL ROWS
        // ======================================================

        function recalculateAllRows() {
            $('#productRows tr.product-row').each(function() {
                calculateRow($(this));
            });

        }

        // ======================================================
        // PURCHASE REQUEST VALIDATION
        // ======================================================

        function validatePurchaseRequestRow(row) {
            if (purchaseType != 'purchase_request') {
                return true;
            }

            let orderedQty = decimal(
                row.find('.ordered-qty').val()
            );

            let quotedQty = decimal(
                row.find('.quoted-qty').val()
            );

            if (orderedQty > quotedQty) {

                errorMessage(
                    'Ordered quantity cannot exceed quoted quantity.'
                );

                row.find('.ordered-qty').focus();

                return false;

            }

            return true;

        }

        // ======================================================
        // RECEIVED VALIDATION
        // ======================================================

        function validateReceivedQuantity(row) {

            let orderedQty = decimal(
                row.find('.ordered-qty').val()
            );

            let receivedQty = decimal(
                row.find('.received-qty').val()
            );

            if (receivedQty > orderedQty) {

                errorMessage(
                    'Received quantity cannot exceed ordered quantity.'
                );

                row.find('.received-qty').focus();

                return false;

            }

            return true;

        }

        // ======================================================
        // ROW VALIDATION
        // ======================================================

        function validateRows() {

            let valid = true;

            $('#productRows tr.product-row').each(function() {

                let row = $(this);

                let product = row.find('.product-select').val();
                let variation = row.find('.variation-select').val();

                let orderedQty = decimal(
                    row.find('.ordered-qty').val()
                );

                let receivedQty = decimal(
                    row.find('.received-qty').val()
                );

                let unitPrice = decimal(
                    row.find('.unit-price').val()
                );

                if (!product) {

                    errorMessage('Please select product.');

                    row.find('.product-select').focus();

                    valid = false;

                    return false;

                }

                if (!variation) {

                    errorMessage('Please select variation.');

                    row.find('.variation-select').focus();

                    valid = false;

                    return false;

                }

                if (orderedQty <= 0) {

                    errorMessage('Ordered quantity is required.');

                    row.find('.ordered-qty').focus();

                    valid = false;

                    return false;

                }

                if (receivedQty < 0) {

                    errorMessage('Received quantity is invalid.');

                    row.find('.received-qty').focus();

                    valid = false;

                    return false;

                }

                if (unitPrice < 0) {

                    errorMessage('Unit price is invalid.');

                    row.find('.unit-price').focus();

                    valid = false;

                    return false;

                }

                if (!validatePurchaseRequestRow(row)) {

                    valid = false;

                    return false;

                }

                if (!validateReceivedQuantity(row)) {

                    valid = false;

                    return false;

                }

                if (row.data('track-serial') && receivedQty > 0) {

                    let enteredCount = row.find('.serial-hidden-input').length;

                    if (enteredCount !== parseFloat(receivedQty)) {

                        errorMessage(
                            `Enter exactly ${receivedQty} serial number(s) for this line (currently ${enteredCount}).`
                        );

                        valid = false;

                        return false;

                    }

                }

            });

            return valid;

        }

        // ======================================================
        // FORM SUBMIT
        // ======================================================

        $('#purchaseForm').on('submit', function(e) {

            if ($('#productRows tr.product-row').length == 0) {

                e.preventDefault();

                errorMessage(
                    'Please add at least one product.'
                );

                return false;

            }

            if (!validateRows()) {

                e.preventDefault();

                return false;

            }

        });


        // ======================================================
        // FORMAT INPUT
        // ======================================================

        $(document).on(
            'blur',
            '.ordered-qty,.received-qty,.unit-price,.row-discount,.row-tax,#discount,#tax,#shipping_charge',
            function() {
                $(this).val(
                    decimal($(this).val())
                );
            }
        );

        // ======================================================
        // UPDATE MODE
        // ======================================================

        function initializeExistingRows() {

            $('#productRows tr.product-row').each(function() {

                calculateRow($(this));

            });

        }
    </script>
@endsection
