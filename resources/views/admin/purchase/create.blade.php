@php
    use App\Enums\RoleNames;
    use Carbon\Carbon;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($purchase) ? 'Update' : 'New' }} Purchase</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($purchase) ? 'Update' : 'Create' }} Purchase</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/purchase') }}" method="POST" id="purchaseForm">
                    @csrf
                    <input type="hidden" name="purchase_id" value="{{ $purchase->purchase_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
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
                                Purchase Type<span class="text-danger">*</span>
                            </label>
                            <select class="form-control" name="purchase_type" id="purchase_type"
                                {{ isset($purchase) ? 'disabled' : '' }}>
                                <option value="direct"
                                    {{ old('purchase_type', $purchase->purchase_type ?? 'direct') == 'direct' ? 'selected' : '' }}>
                                    Direct Purchase
                                </option>
                                <option value="purchase_request"
                                    {{ old('purchase_type', $purchase->purchase_type ?? '') == 'purchase_request' ? 'selected' : '' }}>
                                    Purchase From Purchase Request
                                </option>
                            </select>
                            @if (isset($purchase))
                                <input type="hidden" name="purchase_type" value="{{ $purchase->purchase_type }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3 purchase-request-area">
                            <label>
                                Purchase Request<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="purchase_request_id" id="purchase_request_id"
                                {{ isset($purchase) && $purchase->purchase_type == 'purchase_request' ? 'disabled' : '' }}>
                                <option value="">--Select Purchase Request--</option>
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
                                Quotation<span class="text-danger">*</span>
                            </label>
                            <select class="form-control" id="purchase_request_quotation_id"
                                {{ isset($purchase) && $purchase->purchase_type == 'purchase_request' ? 'disabled' : '' }}>
                                <option value="">--Select Quotation--</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>
                                Supplier<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="supplier_id" id="supplier_id">
                                <option value="">--Select Supplier--</option>
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
                                Warehouse<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="warehouse_id" id="warehouse_id">
                                <option value="">--Select Warehouse--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}"
                                        {{ old('warehouse_id', $purchase->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>PO Number</label>
                            <input type="text" class="form-control" name="purchase_no" readonly
                                value="{{ $purchase->purchase_no ?? ($purchase_no ?? 'Auto Generated') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Purchase Date</label>
                            <input type="text" class="form-control datepicker" name="purchase_date"
                                value="{{ old('purchase_date', isset($purchase) ? localDate($purchase->purchase_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Expected Delivery Date</label>
                            <input type="text" class="form-control datepicker" name="expected_delivery_date"
                                value="{{ old('expected_delivery_date', isset($purchase) ? localDate($purchase->expected_delivery_date) : localDate(date('Y-m-d', strtotime('+7 days')))) }}">
                        </div>
                        <div class="col-md-12">
                            <label>Description</label>
                            <textarea class="form-control" rows="3" name="description">{{ old('description', $purchase->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <hr>
                    {{-- ================= PRODUCT TABLE ================= --}}
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <h5 class="mb-0">
                                Products
                            </h5>
                            <button type="button" class="btn btn-primary" id="addProductBtn">
                                <i class="fa fa-plus"></i>Add Product
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="productTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px;">Product</th>
                                        <th style="min-width:180px;">Variation</th>
                                        <th style="min-width:170px;">Conversion</th>
                                        <th style="min-width:90px;">Unit</th>
                                        <th style="min-width:120px;">Ordered Qty</th>
                                        <th style="min-width:120px;">Received Qty</th>
                                        <th style="min-width:120px;">Unit Price</th>
                                        <th style="min-width:130px;">Subtotal</th>
                                        <th style="min-width:90px;">Disc %</th>
                                        <th style="min-width:130px;">Disc Amount</th>
                                        <th style="min-width:90px;">Tax %</th>
                                        <th style="min-width:130px;">Tax Amount</th>
                                        <th style="min-width:130px">Total</th>
                                        <th style="min-width:60px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    {{-- JS will append rows --}}
                                    @if (!isset($purchase))
                                        <tr id="emptyRow">
                                            <td colspan="14" class="text-center text-muted">
                                                No Products Added
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
                                    <th>Subtotal</th>
                                    <td>
                                        <input class="form-control" id="subtotal" name="subtotal" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Discount Amount</th>
                                    <td>
                                        <input class="form-control" id="discount_amount" name="discount_amount" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tax Amount</th>
                                    <td>
                                        <input class="form-control" id="tax_amount" name="tax_amount" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Shipping</th>
                                    <td>
                                        <input class="form-control" id="shipping_charge" name="shipping_charge">
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total</th>
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
                                {{ isset($purchase) ? 'Update Purchase' : 'Save Purchase' }}
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
        // ======================================================
        // GLOBAL VARIABLES
        // ======================================================
        var productIndex = 0;
        var purchaseType = 'direct';
        var productsData = [];
        // ======================================================
        // DOCUMENT READY
        // ======================================================
        $(function() {
            initializePage();
            bindPurchaseType();
            initializeExistingRows();
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
            if ($('#purchase_id').length) {
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
                    <td colspan="14"
                        class="text-center text-muted">
                        Select Purchase Request
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

            // Clear Purchase Request -> cascades to clear Quotation,
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
            if ($('#purchase_request_id').val()) {
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
                <td colspan="14"
                    class="text-center text-muted">
                    Select Purchase Request
                </td>
            </tr>
        `);
            }
        }

        // ======================================================
        // COMMON PRODUCT ROW TEMPLATE
        // ======================================================
        $(document).off('click').on('click', '#addProductBtn', function() {
            addProductRow();
        });

        function getRowTemplate(data = {}) {
            const index = productIndex;
            return `
    <tr class="product-row">

        <td>
            <input type="hidden"
            name="products[${index}][purchase_request_detail_id]"
            value="${data.purchase_request_detail_id ?? ''}">
            <select
                name="products[${index}][product_id]"
                class="form-control product-select">

                <option value="">--Select Product--</option>

            </select>
        </td>
        <td>
            <select
                name="products[${index}][product_variation_id]"
                class="form-control variation-select">
                <option value="">--Select Variation--</option>
            </select>
        </td>
        <td>
            <select
                name="products[${index}][product_variation_unit_conversion_id]"
                class="form-control conversion-select">
                <option value="">--Select Conversion--</option>
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
                <td colspan="14"
                    class="text-center text-muted">
                    Select Purchase Request
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
            let html = `<option value="">--Select Product--</option>`;
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
            $('#supplier_id').html('<option value="">--Select Supplier--</option>');
            $('#warehouse_id').html('<option value="">--Select Warehouse--</option>');
            productsData = [];
            $('.product-select').each(function() {
                $(this).html('<option value="">--Select Product--</option>');
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
                        .html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select Supplier--</option>';
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
                        .html('<option value="">--Select Supplier--</option>')
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
                        .html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select Warehouse--</option>';
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
                        .html('<option value="">--Select Warehouse--</option>')
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
            let options = `<option value="">--Select Product--</option>`;
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
    <option value="">--Select Variation--</option>
`);
            row.find('.conversion-select').html(`
    <option value="">--Select Conversion--</option>
`);
            row.find('.selected-unit-id').val('');
            row.find('.selected-unit-name').html('-');
            row.find('.conversion-factor').val(1);
            row.find('.unit-price').val(0);
        }

        // ======================================================
        // LOAD VARIATIONS
        // ======================================================

        function loadVariations(productId, row) {
            $.ajax({
                url: url_local + '/admin/product/variation-by-product/' + productId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    row.find('.variation-select')
                        .html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select Variation--</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, variation) {
                            html += `
                    <option
                        value="${variation.product_variation_id}"
                        data-unit-id="${variation.purchase_unit?.unit_id ?? ''}"
                        data-unit-name="${variation.purchase_unit?.name ?? ''}"
                        data-price="${decimal(variation.purchase_price ?? 0)}"
                    >
                        ${variation.name}

                    </option>
                `;

                        });
                    }
                    row.find('.variation-select').html(html);
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

            row.find('.conversion-select').html(`
    <option value="">Loading...</option>
`);

            if (!variationId) {
                return;
            }

            loadConversions(variationId, row);

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
                --Select Conversion--
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
            resetQuotationSelect();
            showSelectPurchaseRequestRow();
            $('#shipping_charge').val(decimal(0));
            calculateGrandTotal();
            if (!purchase_request_id) {
                return;
            }
            loadQuotationsByPurchaseRequest(purchase_request_id);

        });

        // ======================================================
        // RESET QUOTATION SELECT
        // ======================================================

        function resetQuotationSelect() {
            $('#purchase_request_quotation_id')
                .html('<option value="">--Select Quotation--</option>');
        }

        // ======================================================
        // PLACEHOLDER ROWS
        // ======================================================

        function showSelectPurchaseRequestRow() {
            $('#productRows').html(`
        <tr id="emptyRow">
            <td colspan="14" class="text-center text-muted">
                Select Purchase Request
            </td>
        </tr>
    `);
        }

        function showSelectQuotationRow() {
            $('#productRows').html(`
        <tr id="emptyRow">
            <td colspan="14" class="text-center text-muted">
                Select Quotation
            </td>
        </tr>
    `);
        }

        // ======================================================
        // LOAD QUOTATIONS FOR PURCHASE REQUEST
        // ======================================================

        function loadQuotationsByPurchaseRequest(purchase_request_id) {
            $.ajax({
                url: url_local +
                    '/admin/purchase-request-quotation/selected-by-purchase-request/' +
                    purchase_request_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#purchase_request_quotation_id')
                        .prop('disabled', true)
                        .html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select Quotation--</option>';
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
                <td colspan="14" class="text-center">
                    No approved quotation found for this purchase request.
                </td>
            </tr>
        `);
                    }
                    $('#purchase_request_quotation_id')
                        .html(html)
                        .prop('disabled', false);
                    if (response.Success && response.Data.length) {
                        showSelectQuotationRow();
                    }
                },
                error: function() {
                    $('#purchase_request_quotation_id')
                        .html('<option value="">--Select Quotation--</option>')
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
                showSelectQuotationRow();
                calculateGrandTotal();
                return;
            }
            loadPurchaseRequestQuotationDetails(purchase_request_quotation_id);
        });

        // ======================================================
        // LOAD SELECTED QUOTATION DETAILS
        // ======================================================

        function loadPurchaseRequestQuotationDetails(purchase_request_quotation_id) {
            $.ajax({
                url: url_local +
                    '/admin/purchase-request-quotation/details/' +
                    purchase_request_quotation_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#productRows').html(`
            <tr>
                <td colspan="14" class="text-center">
                    <div class="spinner-border spinner-border-sm"></div>
                    Loading...
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

            // Quotation's Other Charges populates Shipping Charges,
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
            <td colspan="14"
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
                `<option value="">--Select Conversion--</option>`;
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

        function loadPurchaseForEdit(purchase) {

            if (!purchase)
                return;

            $('#productRows').html('');

            productIndex = 0;

            $.each(purchase.purchase_details, function(_, item) {

                addProductRow(item);

            });

            calculateGrandTotal();

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
