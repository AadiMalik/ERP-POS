@php
    use App\Enums\RoleNames;
    use Carbon\Carbon;
@endphp
@extends('layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($purchase_request) ? 'Update' : 'New' }} Purchase Request</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($purchase_request) ? 'Update' : 'Create' }} Purchase Request</h5>
            </div>
            <form action="{{ url('admin/purchase-request') }}" method="POST">
                @csrf
                <input type="hidden" name="purchase_request_id"
                    value="{{ isset($purchase_request) ? $purchase_request->purchase_request_id : '' }}">
                <div class="card-body">
                    <!-- Header Information -->
                    <div class="row g-4 mb-4">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                                <select class="form-select" name="business_id" id="business_id" required>
                                    <option value="">-- Select Business --</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $purchase_request->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label class="fw-semibold">Supplier <span class="text-danger">*</span></label>
                            <select class="form-select" name="supplier_id" id="supplier_id" required>
                                <option value="">-- Select Supplier --</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($suppliers as $item)
                                        <option value="{{ $item->supplier_id }}"
                                            {{ old('supplier_id', $purchase_request->supplier_id ?? '') == $item->supplier_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">Warehouse <span class="text-danger">*</span></label>
                            <select class="form-select" name="warehouse_id" id="warehouse_id" required>
                                <option value="">-- Select Warehouse --</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($warehouses as $item)
                                        <option value="{{ $item->warehouse_id }}"
                                            {{ old('warehouse_id', $purchase_request->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">PR Number</label>
                            <input type="text" class="form-control" name="purchase_request_no"
                                value="{{ $purchase_request->purchase_request_no ?? ($purchase_request_no ?? 'Auto Generated') }}"
                                readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">PR Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="purchase_request_date"
                                value="{{ old('purchase_request_date', isset($purchase_request) ? localDate($purchase_request->purchase_request_date) : localDate(date('Y-m-d'))) }}"
                                required>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">Expected Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="purchase_expected_date"
                                value="{{ old('purchase_expected_date', isset($purchase_request) ? localDate($purchase_request->purchase_expected_date) : localDate(date('Y-m-d', strtotime('+7 days')))) }}"
                                required>
                        </div>
                        <div class="col-md-9">
                            <label class="fw-semibold">Description</label>
                            <textarea class="form-control" name="description">{{ old('description', $purchase_request->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Products</h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addProductRow()">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="productTable">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Variation</th>
                                            <th>Unit</th>
                                            <th width="120">Requested Qty</th>
                                            <th width="40">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productRows">
                                        @if (isset($purchase_request) && $purchase_request->purchaseRequestDetails->count() > 0)
                                            @foreach ($purchase_request->purchaseRequestDetails as $detail)
                                                <tr>
                                                    <td>
                                                        <select name="products[{{ $loop->index }}][product_id]"
                                                            class="form-select product-select" required>
                                                            <option value="">--Select Product--</option>
                                                            @foreach ($products as $product)
                                                                <option value="{{ $product->product_id }}"
                                                                    {{ $detail->product_id == $product->product_id ? 'selected' : '' }}>
                                                                    {{ $product->name }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select name="products[{{ $loop->index }}][product_variation_id]"
                                                            class="form-select variation-select">
                                                            <option value="">--Select Variation--</option>
                                                            @if ($detail->product)
                                                                @foreach ($detail->product->productVariations as $variation)
                                                                    <option value="{{ $variation->product_variation_id }}"
                                                                        data-unit-id="{{ $variation->purchase_unit->unit_id ?? '' }}"
                                                                        data-unit-name="{{ $variation->purchase_unit->name ?? '' }}"
                                                                        data-price="{{ $variation->purchase_price ?? 0 }}"
                                                                        {{ $detail->product_variation_id == $variation->product_variation_id ? 'selected' : '' }}>
                                                                        {{ $variation->name }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="products[{{ $loop->index }}][unit_id]"
                                                            class="selected-unit-id" value="{{ $detail->unit_id }}">
                                                        <span
                                                            class="form-control-plaintext selected-unit-name">{{ $detail->unit->name ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][requested_quantity]"
                                                            class="form-control quantity-input"
                                                            onkeypress="return isNumberKey(event)"
                                                            value="{{ decimal($detail->requested_quantity) }}" required>
                                                    </td>
                                                    <td>
                                                        <a href="javascript:void(0)" class="text-danger"
                                                            style="cursor: pointer;" onclick="removeRow(this)">
                                                            <i class="fa fa-trash"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="window.history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">Save Purchase Request</button>
                    </div>
                </div>
            </form>
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
            errorMessage(
                "{{ session('error') }}"
            );
        </script>
    @endif
    <script>
        var productIndex = {{ isset($purchase_request) ? $purchase_request->purchaseRequestDetails->count() : 0 }};

        $(document).ready(function() {
            // Initialize Select2
            $('#business_id, #supplier_id, #warehouse_id').select2();

            // ============================================
            // 2. PRODUCT SELECTION - LOAD VARIATIONS
            // ============================================
            $(document).on('change', '.product-select', function() {
                const row = $(this).closest('tr');
                const productId = $(this).val();
                const variationSelect = row.find('.variation-select');

                // Reset all dropdowns
                let options = '<option value="">--Select Variation--</option>';
                row.find('.conversion-select').html(
                    '<option value="" data-conversion-factor="1" data-to-unit-id="" data-to-unit-name="N/A">--Select Conversion--</option>'
                );

                // Reset unit and price
                row.find('.selected-unit-id').val('');
                row.find('.selected-unit-name').text('N/A');
                row.find('.quantity-input').val(decimal(1));

                // AJAX to fetch variations
                $.ajax({
                    url: "{{ url('admin/product/variation-by-product') }}/" + productId,
                    type: "GET",
                    success: function(response) {
                        let variations = response.Data || [];

                        if (variations.length === 0) {
                            options += `
                                <option value="" 
                                    data-unit-id="" 
                                    data-unit-name="N/A" 
                                    data-price="0">
                                    No Variation Available
                                </option>
                            `;
                        } else {
                            $.each(variations, function(i, variation) {
                                options += `
                                    <option 
                                        value="${variation.product_variation_id}"
                                        data-unit-id="${variation.purchase_unit?.unit_id ?? ''}"
                                        data-unit-name="${variation.purchase_unit?.name ?? 'N/A'}">
                                        ${variation.name}
                                    </option>
                                `;
                            });
                        }
                        variationSelect.html(options);
                        // Trigger change to load first variation's data
                        variationSelect.trigger('change');
                    },
                    error: function() {
                        errorMessage('Failed to load product variations.');
                    }
                });
            });

            // ============================================
            // 3. VARIATION SELECTION - LOAD CONVERSIONS
            // ============================================
            $(document).on('change', '.variation-select', function() {
                const row = $(this).closest('tr');
                const variationId = $(this).val();
                const selectedOption = $(this).find(':selected');

                // Get data attributes from selected option
                const unitId = selectedOption.data('unit-id') || '';
                const unitName = selectedOption.data('unit-name') || 'N/A';
                const price = selectedOption.data('price') || 0;

                // Update unit
                row.find('.selected-unit-id').val(unitId);
                row.find('.selected-unit-name').text(unitName);
            });

            // ============================================
            // 8. NUMBER VALIDATION
            // ============================================
            window.isNumberKey = function(evt) {
                var charCode = (evt.which) ? evt.which : evt.keyCode;
                if (charCode == 46) { // decimal point
                    return true;
                }
                if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                    return false;
                }
                return true;
            };
        });

        // ============================================
        // 11. FUNCTION: ADD PRODUCT ROW
        // ============================================
        function addProductRow() {
            const products = @json($products);
            let productOptions = '<option value="">--Select Product--</option>';

            $.each(products, function(i, product) {
                productOptions += `<option value="${product.product_id}">${product.name}</option>`;
            });

            const html = `
                <tr>
                    <td>
                        <select name="products[${productIndex}][product_id]" class="form-select product-select" required>
                            ${productOptions}
                        </select>
                    </td>
                    <td>
                        <select name="products[${productIndex}][product_variation_id]" class="form-select variation-select">
                            <option value="">--Select Variation--</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" name="products[${productIndex}][unit_id]" class="selected-unit-id" value="">
                        <span class="form-control-plaintext selected-unit-name">N/A</span>
                    </td>
                    <td>
                        <input type="text" name="products[${productIndex}][requested_quantity]" 
                               class="form-control quantity-input" value="1" onkeypress="return isNumberKey(event)" required>
                    </td>
                    <td>
                        <a href="javascript:void(0)" class="text-danger" style="cursor: pointer;" onclick="removeRow(this)">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
            `;

            $('#productRows').append(html);
            productIndex++;

            const lastRow = $('#productRows tr:last');
            lastRow.find('.product-select').focus();
        }

        // ============================================
        // 12. FUNCTION: REMOVE ROW
        // ============================================
        function removeRow(button) {
            if ($('#productRows tr').length > 1) {
                const row = $(button).closest('tr');
                row.fadeOut(300, function() {
                    $(this).remove();
                });
            } else {
                errorMessage('At least one product is required.');
                $(button).closest('tr').addClass('shake');
                setTimeout(function() {
                    $(button).closest('tr').removeClass('shake');
                }, 500);
            }
        }

        // ============================================
        // 13. ERROR MESSAGE FUNCTION
        // ============================================
        if (typeof errorMessage !== 'function') {
            function errorMessage(message) {
                alert(message);
                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                }
            }
        }

        // ============================================
        // 14. CSS STYLES
        // ============================================
        $('head').append(`
            <style>
                .shake {
                    animation: shake 0.5s ease-in-out;
                }
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-10px); }
                    75% { transform: translateX(10px); }
                }
                .row-total, #subtotal, #grand_total {
                    font-weight: bold;
                }
                #productRows tr {
                    transition: background-color 0.2s;
                }
                #productRows tr:hover {
                    background-color: #f8f9fa;
                }
                .quantity-input:focus, .price-input:focus {
                    border-color: #0d6efd;
                    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
                }
                .conversion-select {
                    background-color: #f8f9fa;
                }
                .selected-unit-name {
                    font-weight: 500;
                    color: #0d6efd;
                }
            </style>
        `);

        $('#business_id').on('change', function() {

            let business_id = $(this).val();

            // Reset dropdowns
            $('#branch_id').html('<option value="">--Select Branch--</option>');
            $('#supplier_id').html('<option value="">--Select Supplier--</option>');
            $('#warehouse_id').html('<option value="">--Select Warehouse--</option>');
            $('#product_id').html('<option value="">--Select Product--</option>');

            if (!business_id) {
                return;
            }

            Promise.all([
                    ajaxRequest({
                        url: url_local + '/admin/branch/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/supplier/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/warehouse/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/product/by-business/' + business_id,
                        data: {}
                    })
                ])
                .then(([branchRes, supplierRes, warehouseRes, productRes]) => {

                    // Branches
                    let branchOptions = '<option value="">--Select Branch--</option>';
                    $.each(branchRes.Data, function(_, item) {
                        branchOptions += `<option value="${item.branch_id}">
                                ${item.code} ${item.name}
                              </option>`;
                    });
                    $('#branch_id').html(branchOptions);

                    // Suppliers
                    let supplierOptions = '<option value="">--Select Supplier--</option>';
                    $.each(supplierRes.Data, function(_, item) {
                        supplierOptions += `<option value="${item.supplier_id}">
                                    ${item.code} ${item.name}
                                </option>`;
                    });
                    $('#supplier_id').html(supplierOptions);

                    // Warehouses
                    let warehouseOptions = '<option value="">--Select Warehouse--</option>';
                    $.each(warehouseRes.Data, function(_, item) {
                        warehouseOptions += `<option value="${item.warehouse_id}">
                                    ${item.name}
                                </option>`;
                    });
                    $('#warehouse_id').html(warehouseOptions);
                    // Products
                    products = productRes.Data || [];

                    // Existing rows update
                    let productOptions = '<option value="">--Select Product--</option>';

                    $.each(products, function(_, item) {
                        productOptions +=
                            `<option value="${item.product_id}">${item.code} ${item.name}</option>`;
                    });

                    $('.product-select').each(function() {

                        let selected = $(this).val();

                        $(this).html(productOptions);

                        if (selected) {
                            $(this).val(selected);
                        }
                    });

                })
                .catch((err) => {
                    errorMessage(err.Message ?? 'Something went wrong.');
                });

        });
    </script>
@endsection
