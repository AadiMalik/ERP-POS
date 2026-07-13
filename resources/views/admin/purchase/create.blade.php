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
            <form action="{{ url('admin/purchase') }}" method="POST">
                @csrf
                <input type="hidden" name="purchase_id" value="{{ isset($purchase) ? $purchase->purchase_id : '' }}">
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
                                            {{ old('business_id', $purchase->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="fw-semibold">Purchase Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="purchase_type" id="purchase_type" required>
                                <option value="direct"
                                    {{ old('purchase_type', $purchase->purchase_type ?? 'direct') == 'direct' ? 'selected' : '' }}>
                                    Direct Purchase
                                </option>
                                <option value="purchase"
                                    {{ old('purchase_type', $purchase->purchase_type ?? '') == 'purchase_request' ? 'selected' : '' }}>
                                    Purchase From Purchase Request
                                </option>
                            </select>
                        </div>
                        <div
                            class="col-md-3 purchase-request-section {{ old('purchase_type', $purchase->purchase_type ?? 'direct') == 'purchase_request' ? '' : 'd-none' }}">
                            <label class="fw-semibold">Purchase Request<span class="text-danger">*</span></label>
                            <select class="form-select" name="purchase_request_id" id="purchase_request_id">
                                <option value="">-- Select Purchase Request --</option>
                                @foreach ($purchase_requests as $item)
                                    <option value="{{ $item->purchase_request_id }}"
                                        {{ old('purchase_request_id', $purchase->purchase_request_id ?? '') == $item->purchase_request_id ? 'selected' : '' }}>
                                        {{ $item->purchase_request_no }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold">Supplier <span class="text-danger">*</span></label>
                            <select class="form-select" name="supplier_id" id="supplier_id" required>
                                <option value="">-- Select Supplier --</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($suppliers as $item)
                                        <option value="{{ $item->supplier_id }}"
                                            {{ old('supplier_id', $purchase->supplier_id ?? '') == $item->supplier_id ? 'selected' : '' }}>
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
                                            {{ old('warehouse_id', $purchase->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">PO Number</label>
                            <input type="text" class="form-control" name="purchase_no"
                                value="{{ $purchase->purchase_no ?? ($purchase_no ?? 'Auto Generated') }}" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">PO Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="purchase_date"
                                value="{{ old('purchase_date', isset($purchase) ? localDate($purchase->purchase_date) : localDate(date('Y-m-d'))) }}"
                                required>
                        </div>
                        <div class="col-md-3">
                            <label class="fw-semibold">PO Delivery Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="expected_delivery_date"
                                value="{{ old('expected_delivery_date', isset($purchase) ? localDate($purchase->expected_delivery_date) : localDate(date('Y-m-d', strtotime('+7 days')))) }}"
                                required>
                        </div>

                        <div class="col-md-9">
                            <label class="fw-semibold">Description</label>
                            <textarea class="form-control" name="description">{{ old('description', $purchase->description ?? '') }}</textarea>
                        </div>
                    </div>

                    <!-- Products Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Products</h6>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addProductRow()"
                                id="addProductBtn">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="productTable">
                                    <thead>
                                        <tr>
                                            <th style="min-width: 150px;">Product</th>
                                            <th style="min-width: 150px;">Variation</th>
                                            <th style="min-width: 150px;">Conversion</th>
                                            <th style="min-width: 80px;">Unit</th>
                                            <th style="min-width: 120px;">
                                                Ordered Qty
                                                {{-- <small class="text-muted d-block" style="font-size: 10px;">
                                                    <span id="qtyHint">(Enter quantity)</span>
                                                </small> --}}
                                            </th>
                                            <th style="min-width: 120px;">Unit Price</th>
                                            <th style="min-width: 130px;" class="currency-label">Subtotal</th>
                                            <th style="min-width: 80px;">Disc %</th>
                                            <th style="min-width: 100px;" class="currency-label">Disc Amount</th>
                                            <th style="min-width: 80px;">Tax %</th>
                                            <th style="min-width: 100px;" class="currency-label">Tax Amount</th>
                                            <th style="min-width: 120px;" class="currency-label">Total</th>
                                            <th style="min-width: 50px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productRows">
                                        @if (isset($purchase) && $purchase->purchaseDetails->count() > 0)
                                            @foreach ($purchase->purchaseDetails as $detail)
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
                                                                        data-requested-qty="0"
                                                                        {{ $detail->product_variation_id == $variation->product_variation_id ? 'selected' : '' }}>
                                                                        {{ $variation->name }}
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <select
                                                            name="products[{{ $loop->index }}][product_variation_unit_conversion_id]"
                                                            class="form-select conversion-select">
                                                            <option value="" data-conversion-factor="1"
                                                                data-to-unit-id="" data-to-unit-name="N/A">--Select
                                                                Conversion--</option>
                                                            @if ($detail->productVariation)
                                                                @foreach ($detail->productVariation->productVariationUnitConversion as $conversion)
                                                                    <option
                                                                        value="{{ $conversion->product_variation_unit_conversion_id }}"
                                                                        data-from-unit-id="{{ $conversion->from_unit_id }}"
                                                                        data-from-unit-name="{{ $conversion->fromUnit->name ?? '' }}"
                                                                        data-to-unit-id="{{ $conversion->to_unit_id }}"
                                                                        data-to-unit-name="{{ $conversion->toUnit->name ?? '' }}"
                                                                        data-conversion-factor="{{ $conversion->conversion_factor }}"
                                                                        {{ $detail->product_variation_unit_conversion_id == $conversion->product_variation_unit_conversion_id ? 'selected' : '' }}>
                                                                        {{ $conversion->fromUnit->name ?? 'N/A' }} to
                                                                        {{ $conversion->toUnit->name ?? 'N/A' }}
                                                                        ({{ $conversion->conversion_factor }})
                                                                    </option>
                                                                @endforeach
                                                            @endif
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <input type="hidden"
                                                            name="products[{{ $loop->index }}][unit_id]"
                                                            class="selected-unit-id" value="{{ $detail->unit_id }}">
                                                        <span
                                                            class="form-control-plaintext selected-unit-name">{{ $detail->unit->name ?? 'N/A' }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="input-group">
                                                            <input type="text"
                                                                name="products[{{ $loop->index }}][ordered_quantity]"
                                                                class="form-control quantity-input"
                                                                onkeypress="return isNumberKey(event)"
                                                                value="{{ decimal($detail->ordered_quantity) }}"
                                                                data-requested-qty="0"
                                                                {{ old('purchase_type', $purchase->purchase_type ?? 'direct') == 'purchase' ? 'readonly' : '' }}
                                                                required>
                                                            @if (old('purchase_type', $purchase->purchase_type ?? 'direct') == 'purchase')
                                                                <span class="input-group-text bg-light">
                                                                    <small>Max: 0</small>
                                                                </span>
                                                            @endif
                                                        </div>
                                                        <input type="hidden"
                                                            name="products[{{ $loop->index }}][conversion_factor]"
                                                            class="conversion-factor"
                                                            value="{{ $detail->conversion_factor ?? 1 }}">
                                                        @if ($detail->purchase_request_detail_id)
                                                            <input type="hidden"
                                                                name="products[{{ $loop->index }}][purchase_request_detail_id]"
                                                                value="{{ $detail->purchase_request_detail_id }}">
                                                            <input type="hidden"
                                                                name="products[{{ $loop->index }}][requested_quantity]"
                                                                class="requested-quantity"
                                                                value="{{ $detail->requested_quantity ?? 0 }}">
                                                        @endif
                                                        <input type="hidden"
                                                            name="products[{{ $loop->index }}][rejected_quantity]"
                                                            class="rejected-quantity-input" value="0">
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][unit_price]"
                                                            class="form-control price-input"
                                                            onkeypress="return isNumberKey(event)"
                                                            value="{{ decimal($detail->unit_price) }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][subtotal]"
                                                            class="form-control row-subtotal"
                                                            value="{{ decimal($detail->subtotal ?? 0) }}"
                                                            readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][discount]"
                                                            class="form-control row-discount-input"
                                                            onkeypress="return isNumberKey(event)"
                                                            value="{{ decimal($detail->discount ?? 0) }}">
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][discount_amount]"
                                                            class="form-control row-discount-amount"
                                                            value="{{ decimal($detail->discount_amount ?? 0) }}"
                                                            readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][tax]"
                                                            class="form-control row-tax-input"
                                                            onkeypress="return isNumberKey(event)"
                                                            value="{{ decimal($detail->tax ?? 0) }}">
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][tax_amount]"
                                                            class="form-control row-tax-amount"
                                                            value="{{ decimal($detail->tax_amount ?? 0) }}"
                                                            readonly>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control row-total"
                                                            name="products[{{ $loop->index }}][total]"
                                                            value="{{ decimal($detail->total) }}" readonly>
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
                                    <tfoot>
                                        <tr>
                                            <td colspan="10" class="text-end"><strong
                                                    class="currency-label">Subtotal</strong></td>
                                            <td colspan="3">
                                                <input type="text" name="subtotal" id="subtotal"
                                                    class="form-control"
                                                    value="{{ old('subtotal', decimal($purchase->subtotal ?? 0)) }}"
                                                    readonly>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="text-end"><strong>Discount (%)</strong></td>
                                            <td colspan="3">
                                                <input type="text" name="discount" id="discount"
                                                    class="form-control discount-input"
                                                    onkeypress="return isNumberKey(event)"
                                                    value="{{ old('discount', decimal($purchase->discount ?? 0)) }}">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="text-end"><strong class="currency-label">Discount
                                                    Amount</strong></td>
                                            <td colspan="3">
                                                <input type="text" name="discount_amount" id="discount_amount"
                                                    class="form-control"
                                                    value="{{ old('discount_amount', decimal($purchase->discount_amount ?? 0)) }}"
                                                    readonly>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="text-end"><strong>Tax (%)</strong></td>
                                            <td colspan="3">
                                                <input type="text" name="tax" id="tax"
                                                    class="form-control tax-input" onkeypress="return isNumberKey(event)"
                                                    value="{{ old('tax', decimal($purchase->tax ?? 0)) }}">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="text-end"><strong class="currency-label">Tax
                                                    Amount</strong></td>
                                            <td colspan="3">
                                                <input type="text" name="tax_amount" id="tax_amount"
                                                    class="form-control"
                                                    value="{{ old('tax_amount', decimal($purchase->tax_amount ?? 0)) }}"
                                                    readonly>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="text-end"><strong class="currency-label">Shipping
                                                    Charge</strong></td>
                                            <td colspan="3">
                                                <input type="text" name="shipping_charge"
                                                    onkeypress="return isNumberKey(event)" id="shipping_charge"
                                                    class="form-control shipping-input"
                                                    value="{{ old('shipping_charge', decimal($purchase->shipping_charge ?? 0)) }}">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="10" class="text-end"><strong class="currency-label">Total</strong></td>
                                            <td colspan="3">
                                                <input type="text" name="total" id="total"
                                                    class="form-control"
                                                    value="{{ old('total', decimal($purchase->total ?? 0)) }}" readonly>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card-footer border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="window.history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4">{{ isset($purchase) ? 'Update' : 'Save' }}
                            Purchase</button>
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
            errorMessage("{{ session('error') }}");
        </script>
    @endif
    <script>
        // ============================================
        // GLOBAL VARIABLES
        // ============================================
        var productIndex = 0;

        $(document).ready(function() {
            // Initialize Select2
            if (typeof $.fn.select2 !== 'undefined') {
                $('#business_id, #supplier_id, #warehouse_id, #purchase_request_id').select2();
            }

            // Set initial product index based on existing rows
            productIndex = $('#productRows tr').length || 0;

            // ============================================
            // 1. PURCHASE TYPE TOGGLE
            // ============================================
            $('#purchase_type').change(function() {
                let type = $(this).val();
                if (type === 'direct') {
                    $('.purchase-request-section').addClass('d-none');
                    $('#addProductBtn').show();
                    $('#qtyHint').text('(Enter quantity)');
                    $('#productRows').empty();
                    productIndex = 0;
                    addProductRow();
                    $('#purchase_request_id').val('').trigger('change');
                } else {
                    $('.purchase-request-section').removeClass('d-none');
                    $('#addProductBtn').hide();
                    $('#qtyHint').html('(Max: <span id="maxQtyHint">0</span>)');
                    $('#productRows').html(
                        '<tr><td colspan="13" class="text-center text-muted">Select a purchase request to load products</td></tr>'
                    );
                    calculateGrandTotals();
                }
            });

            // ============================================
            // 2. ENABLE/DISABLE MANUAL QUANTITY INPUT
            // ============================================
            function enableManualQuantityInput(enable) {
                if (enable) {
                    $('.quantity-input').prop('readonly', false).removeClass('bg-light');
                    $('.quantity-input').attr('title', 'Enter quantity manually');
                    $('.quantity-input').closest('.input-group').find('.input-group-text').remove();
                } else {
                    $('.quantity-input').prop('readonly', true).addClass('bg-light');
                    $('.quantity-input').attr('title', 'Quantity is controlled by purchase request');
                }
            }

            // ============================================
            // 3. PURCHASE REQUEST SELECTION
            // ============================================
            $('#purchase_request_id').change(function() {
                let id = $(this).val();
                if (!id) {
                    if ($('#purchase_type').val() === 'direct') {
                        $('#productRows').empty();
                        productIndex = 0;
                        addProductRow();
                    } else {
                        $('#productRows').html(
                            '<tr><td colspan="13" class="text-center text-muted">Select a purchase request to load products</td></tr>'
                        );
                    }
                    calculateGrandTotals();
                    return;
                }

                $('#productRows').html(
                    '<tr><td colspan="13" class="text-center"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Loading purchase request details...</td></tr>'
                );

                $.ajax({
                    url: url_local + '/admin/purchase-request/details/' + id,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.Success) {
                            loadPurchaseRequest(response.Data);
                        } else {
                            errorMessage(response.Message || 'Failed to load purchase request details.');
                            $('#productRows').html(
                                '<tr><td colspan="13" class="text-center text-danger">' + (response
                                    .Message || 'Failed to load products') + '</td></tr>'
                            );
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'Failed to load purchase request details. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.Message) {
                            errorMsg = xhr.responseJSON.Message;
                        }
                        errorMessage(errorMsg);
                        $('#productRows').html(
                            '<tr><td colspan="13" class="text-center text-danger">' + errorMsg + '</td></tr>'
                        );
                    }
                });
            });

            // ============================================
            // 4. LOAD PURCHASE REQUEST DATA
            // ============================================
            function loadPurchaseRequest(data) {
                try {
                    console.log(data.header)
                    if (data.header) {
                        if (data.header.supplier_id) {
                            $('#supplier_id').val(data.header.supplier_id).trigger('change');
                        }
                        if (data.header.warehouse_id) {
                            $('#warehouse_id').val(data.header.warehouse_id).trigger('change');
                        }
                        if (data.header.branch_id && $('#branch_id').length) {
                            $('#branch_id').val(data.header.branch_id).trigger('change');
                        }
                        if (data.header.purchase_request_date) {
                            $('input[name=purchase_date]').val(data.header.purchase_request_date).trigger('change');
                        }
                        if (data.header.purchase_expected_date) {
                            $('input[name=expected_delivery_date]').val(data.header.purchase_expected_date).trigger('change');
                        }
                        if (data.header.description) {
                            $('textarea[name=description]').val(data.header.description);
                        }
                    }

                    $('#productRows').empty();
                    productIndex = 0;

                    if (data.details && data.details.length > 0) {
                        $.each(data.details, function(i, item) {
                            addPurchaseRequestRow(item);
                        });
                    } else {
                        $('#productRows').html(
                            '<tr><td colspan="13" class="text-center text-muted">No products found in this purchase request.</td></tr>'
                        );
                    }

                    setTimeout(function() {
                        calculateGrandTotals();
                    }, 300);

                } catch (error) {
                    console.error('Error in loadPurchaseRequest:', error);
                    errorMessage('Error loading purchase request data.');
                    $('#productRows').html(
                        '<tr><td colspan="13" class="text-center text-danger">Error loading products</td></tr>'
                    );
                }
            }

            // ============================================
            // 5. ADD PURCHASE REQUEST ROW
            // ============================================
            function addPurchaseRequestRow(item) {
                let productOptions = '<option value="">--Select Product--</option>';
                if (window.productsData && window.productsData.length) {
                    $.each(window.productsData, function(i, product) {
                        productOptions += `
                                <option value="${product.product_id}"
                                    ${product.product_id == item.product_id ? 'selected' : ''}>
                                    ${product.name}
                                </option>
                            `;
                    });
                }

                let variationOptions = '<option value="">--Select Variation--</option>';
                if (item.product_variation_id) {
                    variationOptions += `
                            <option value="${item.product_variation_id}" 
                                data-unit-id="${item.unit_id || ''}"
                                data-unit-name="${item.unit_name || 'N/A'}"
                                data-price="${item.unit_price || 0}"
                                data-requested-qty="${item.requested_quantity || 0}"
                                selected>
                                ${item.variation_name || 'Variation'}
                            </option>
                        `;
                }

                let conversionOptions =
                    '<option value="" data-conversion-factor="1" data-to-unit-id="" data-to-unit-name="N/A">--Select Conversion--</option>';
                if (item.conversions && item.conversions.length) {
                    $.each(item.conversions, function(i, conversion) {
                        conversionOptions += `
                                <option value="${conversion.product_variation_unit_conversion_id}"
                                    data-from-unit-id="${conversion.from_unit_id || ''}"
                                    data-from-unit-name="${conversion.from_unit_name || 'N/A'}"
                                    data-to-unit-id="${conversion.to_unit_id || ''}"
                                    data-to-unit-name="${conversion.to_unit_name || 'N/A'}"
                                    data-conversion-factor="${decimal(conversion.conversion_factor)}"
                                    ${item.product_variation_unit_conversion_id == conversion.product_variation_unit_conversion_id ? 'selected' : ''}>
                                    ${conversion.from_unit_name || 'N/A'} to ${conversion.to_unit_name || 'N/A'} 
                                    (${decimal(conversion.conversion_factor)})
                                </option>
                            `;
                    });
                }

                const quantity = parseFloat(item.ordered_quantity) || parseFloat(item.requested_quantity) || 1;
                const price = parseFloat(item.unit_price) || 0;
                const conversionFactor = parseFloat(item.conversion_factor) || 1;
                const requestedQty = parseFloat(item.requested_quantity) || 0;
                const discount = parseFloat(item.discount) || 0;
                const tax = parseFloat(item.tax) || 0;

                // Calculate row values
                const subtotal = quantity * conversionFactor * price;
                const discountAmount = (subtotal * discount) / 100;
                const afterDiscount = subtotal - discountAmount;
                const taxAmount = (afterDiscount * tax) / 100;
                const total = afterDiscount + taxAmount;

                const rowHtml = `
                        <tr>
                            <td>
                                <select name="products[${productIndex}][product_id]" class="form-select product-select" required>
                                    ${productOptions}
                                </select>
                            </td>
                            <td>
                                <select name="products[${productIndex}][product_variation_id]" class="form-select variation-select">
                                    ${variationOptions}
                                </select>
                            </td>
                            <td>
                                <select name="products[${productIndex}][product_variation_unit_conversion_id]" class="form-select conversion-select">
                                    ${conversionOptions}
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="products[${productIndex}][unit_id]" class="selected-unit-id" value="${item.unit_id || ''}">
                                <span class="form-control-plaintext selected-unit-name">${item.unit_name || 'N/A'}</span>
                            </td>
                            <td>
                                <div class="input-group">
                                    <input type="text" class="form-control quantity-input" 
                                           name="products[${productIndex}][ordered_quantity]" 
                                           value="${quantity}" 
                                           onkeypress="return isNumberKey(event)"
                                           data-requested-qty="${requestedQty}">
                                    ${$('#purchase_type').val() === 'purchase_request' ? '<span class="input-group-text bg-light"><small>Max: ' + requestedQty + '</small></span>' : ''}
                                </div>
                                <input type="hidden" name="products[${productIndex}][conversion_factor]" 
                                       class="conversion-factor" value="${conversionFactor}">
                                ${item.purchase_request_detail_id ? `<input type="hidden" name="products[${productIndex}][purchase_request_detail_id]" value="${item.purchase_request_detail_id}">` : ''}
                                ${item.requested_quantity ? `<input type="hidden" name="products[${productIndex}][requested_quantity]" class="requested-quantity" value="${requestedQty}">` : ''}
                                <input type="hidden" name="products[${productIndex}][rejected_quantity]" class="rejected-quantity-input" value="0">
                            </td>
                            <td>
                                <input type="text" class="form-control price-input" 
                                       name="products[${productIndex}][unit_price]" 
                                       value="${price}" 
                                       onkeypress="return isNumberKey(event)" required>
                            </td>
                            <td>
                                <input type="text" class="form-control row-subtotal" 
                                       name="products[${productIndex}][subtotal]" 
                                       value="${subtotal.toFixed(decimal_points || 2)}" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control row-discount-input" 
                                       name="products[${productIndex}][discount]" 
                                       value="${discount}" 
                                       onkeypress="return isNumberKey(event)">
                            </td>
                            <td>
                                <input type="text" class="form-control row-discount-amount" 
                                       name="products[${productIndex}][discount_amount]" 
                                       value="${discountAmount.toFixed(decimal_points || 2)}" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control row-tax-input" 
                                       name="products[${productIndex}][tax]" 
                                       value="${tax}" 
                                       onkeypress="return isNumberKey(event)">
                            </td>
                            <td>
                                <input type="text" class="form-control row-tax-amount" 
                                       name="products[${productIndex}][tax_amount]" 
                                       value="${taxAmount.toFixed(decimal_points || 2)}" readonly>
                            </td>
                            <td>
                                <input type="text" class="form-control row-total" 
                                       name="products[${productIndex}][total]" 
                                       value="${total.toFixed(decimal_points || 2)}" readonly>
                            </td>
                            <td>
                                <a href="javascript:void(0)" onclick="removeRow(this)" class="text-danger" title="Remove product">
                                    <i class="fa fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    `;

                $('#productRows').append(rowHtml);
                productIndex++;

                const row = $('#productRows tr:last');
                initializeProductRow(row);

                if (item.product_variation_id) {
                    const variationSelect = row.find('.variation-select');
                    variationSelect.trigger('change');
                }

                calculateRowTotal(row);
            }

            // ============================================
            // 6. INITIALIZE PRODUCT ROW
            // ============================================
            function initializeProductRow(row) {
                const variationSelect = row.find('.variation-select');
                const selectedVariation = variationSelect.find(':selected');
                if (selectedVariation.val()) {
                    const unitId = selectedVariation.data('unit-id') || '';
                    const unitName = selectedVariation.data('unit-name') || 'N/A';
                    const price = selectedVariation.data('price') || 0;
                    const requestedQty = selectedVariation.data('requested-qty') || 0;

                    row.find('.selected-unit-id').val(unitId);
                    row.find('.selected-unit-name').text(unitName);
                    row.find('.price-input').val(decimal(price));

                    if (requestedQty > 0) {
                        const qtyInput = row.find('.quantity-input');
                        qtyInput.data('requested-qty', requestedQty);
                        qtyInput.attr('max', requestedQty);
                        row.find('.input-group-text small').text('Max: ' + requestedQty);
                        $('#maxQtyHint').text(requestedQty);
                    }
                }

                const conversionSelect = row.find('.conversion-select');
                const selectedConversion = conversionSelect.find(':selected');
                if (selectedConversion.val()) {
                    const factor = parseFloat(selectedConversion.data('conversion-factor')) || 1;
                    const toUnitId = selectedConversion.data('to-unit-id') || '';
                    const toUnitName = selectedConversion.data('to-unit-name') || 'N/A';

                    row.find('.conversion-factor').val(decimal(factor));
                    row.find('.selected-unit-id').val(toUnitId);
                    row.find('.selected-unit-name').text(toUnitName);
                }
            }

            // ============================================
            // 7. ADD PRODUCT ROW (Direct Purchase)
            // ============================================
            window.addProductRow = function() {
                const products = window.productsData || [];
                let productOptions = '<option value="">--Select Product--</option>';

                $.each(products, function(i, product) {
                    productOptions += `
                            <option value="${product.product_id}">${product.name}</option>
                        `;
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
                                <select name="products[${productIndex}][product_variation_unit_conversion_id]" class="form-select conversion-select">
                                    <option value="" data-conversion-factor="1" data-to-unit-id="" data-to-unit-name="N/A">--Select Conversion--</option>
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="products[${productIndex}][unit_id]" class="selected-unit-id" value="">
                                <span class="form-control-plaintext selected-unit-name">N/A</span>
                            </td>
                            <td>
                                <input type="text" name="products[${productIndex}][ordered_quantity]" 
                                       class="form-control quantity-input" value="1" 
                                       onkeypress="return isNumberKey(event)" required>
                                <input type="hidden" name="products[${productIndex}][conversion_factor]" 
                                       class="conversion-factor" value="1">
                            </td>
                            <td>
                                <input type="text" name="products[${productIndex}][unit_price]" 
                                       class="form-control price-input" value="0" 
                                       onkeypress="return isNumberKey(event)" required>
                            </td>
                            <td>
                                <input type="text" name="products[${productIndex}][subtotal]" 
                                       class="form-control row-subtotal" value="0.00" readonly>
                            </td>
                            <td>
                                <input type="text" name="products[${productIndex}][discount]" 
                                       class="form-control row-discount-input" value="0" 
                                       onkeypress="return isNumberKey(event)">
                            </td>
                            <td>
                                <input type="text" name="products[${productIndex}][discount_amount]" 
                                       class="form-control row-discount-amount" value="0.00" readonly>
                            </td>
                            <td>
                                <input type="text" name="products[${productIndex}][tax]" 
                                       class="form-control row-tax-input" value="0" 
                                       onkeypress="return isNumberKey(event)">
                            </td>
                            <td>
                                <input type="text" name="products[${productIndex}][tax_amount]" 
                                       class="form-control row-tax-amount" value="0.00" readonly>
                            </td>
                            <td>
                                <input type="text" name="products[${productIndex}][total]" 
                                       class="form-control row-total" value="0.00" readonly>
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
                calculateGrandTotals();
            };

            // ============================================
            // 8. REMOVE ROW
            // ============================================
            window.removeRow = function(button) {
                const row = $(button).closest('tr');

                if ($('#productRows tr').length <= 1) {
                    errorMessage('At least one product is required.');
                    row.addClass('shake');
                    setTimeout(function() {
                        row.removeClass('shake');
                    }, 500);
                    return;
                }

                row.fadeOut(300, function() {
                    $(this).remove();
                    calculateGrandTotals();
                });
            };

            // ============================================
            // 9. CALCULATE ROW TOTAL
            // ============================================
            window.calculateRowTotal = function(row) {
                if (!row || !row.length) return 0;

                const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
                const price = parseFloat(row.find('.price-input').val()) || 0;
                const conversionFactor = parseFloat(row.find('.conversion-factor').val()) || 1;
                const requestedQty = parseFloat(row.find('.quantity-input').data('requested-qty')) || 0;

                if (requestedQty > 0 && quantity > requestedQty) {
                    row.find('.quantity-input').val(requestedQty);
                    errorMessage('Order quantity cannot exceed requested quantity (' + requestedQty + ')');
                    calculateRowTotal(row);
                    calculateGrandTotals();
                    return 0;
                }

                // Calculate subtotal
                const subtotal = quantity * conversionFactor * price;

                // Calculate row discount
                const discountPercent = parseFloat(row.find('.row-discount-input').val()) || 0;
                const discountAmount = (subtotal * discountPercent) / 100;
                const afterDiscount = subtotal - discountAmount;

                // Calculate row tax
                const taxPercent = parseFloat(row.find('.row-tax-input').val()) || 0;
                const taxAmount = (afterDiscount * taxPercent) / 100;
                const total = afterDiscount + taxAmount;

                // Update row fields
                row.find('.row-subtotal').val(subtotal.toFixed(decimal_points || 2));
                row.find('.row-discount-amount').val(discountAmount.toFixed(decimal_points || 2));
                row.find('.row-tax-amount').val(taxAmount.toFixed(decimal_points || 2));
                row.find('.row-total').val(total.toFixed(decimal_points || 2));

                // Calculate rejected quantity for purchase request
                if (requestedQty > 0) {
                    const rejectedQty = requestedQty - quantity;
                    const rejectedInput = row.find('.rejected-quantity-input');
                    if (rejectedInput.length) {
                        rejectedInput.val(rejectedQty > 0 ? rejectedQty : 0);
                    }

                    row.find('.rejected-info').remove();
                    if (rejectedQty > 0) {
                        row.find('.quantity-input').closest('td').append(`
                                <span class="rejected-info text-warning small d-block">
                                    <i class="fa fa-exclamation-triangle"></i> Rejected: ${rejectedQty} ${row.find('.selected-unit-name').text() || 'units'}
                                </span>
                            `);
                    }
                }

                // Color coding for total
                const totalInput = row.find('.row-total');
                if (total < 0) {
                    totalInput.css('color', 'red');
                } else if (total === 0) {
                    totalInput.css('color', '#6c757d');
                } else {
                    totalInput.css('color', '#198754');
                }

                return total;
            };

            // ============================================
            // 10. CALCULATE GRAND TOTALS
            // ============================================
            window.calculateGrandTotals = function() {
                let grandSubtotal = 0;
                let totalDiscountAmount = 0;
                let totalTaxAmount = 0;

                // Calculate totals from all rows
                $('#productRows tr').each(function() {
                    const total = parseFloat($(this).find('.row-total').val()) || 0;
                    
                    grandSubtotal += total;
                });

                // Get master discount, tax, shipping
                const masterDiscountPercent = parseFloat($('#discount').val()) || 0;
                const masterTaxPercent = parseFloat($('#tax').val()) || 0;
                const shippingCharge = parseFloat($('#shipping_charge').val()) || 0;

                // Validate master discount
                if (masterDiscountPercent > 100) {
                    $('#discount').val(100);
                    errorMessage('Discount cannot exceed 100%');
                    return;
                }

                // Calculate master discount and tax on grand subtotal
                const masterDiscountAmount = (grandSubtotal * masterDiscountPercent) / 100;
                const afterMasterDiscount = grandSubtotal - masterDiscountAmount;
                const masterTaxAmount = (afterMasterDiscount * masterTaxPercent) / 100;
                const grandTotal = afterMasterDiscount + masterTaxAmount + shippingCharge;

                // Update master fields
                $('#subtotal').val(grandSubtotal.toFixed(decimal_points || 2));
                $('#discount_amount').val(masterDiscountAmount.toFixed(decimal_points || 2));
                $('#tax_amount').val(masterTaxAmount.toFixed(decimal_points || 2));
                $('#total').val(grandTotal.toFixed(decimal_points || 2));

                // Color coding for grand total
                const grandTotalInput = $('#total');
                if (grandTotal < 0) {
                    grandTotalInput.css('color', 'red');
                } else if (grandTotal === 0) {
                    grandTotalInput.css('color', '#6c757d');
                } else {
                    grandTotalInput.css('color', '#198754');
                }

                // Show/hide master discount and tax rows
                if (masterDiscountPercent > 0) {
                    $('#discount_amount').closest('tr').show();
                } else {
                    $('#discount_amount').closest('tr').hide();
                }

                if (masterTaxPercent > 0) {
                    $('#tax_amount').closest('tr').show();
                } else {
                    $('#tax_amount').closest('tr').hide();
                }

                return grandTotal;
            };

            // ============================================
            // 11. ROW DISCOUNT/TAX CHANGES
            // ============================================
            $(document).on('input change', '.row-discount-input, .row-tax-input', function() {
                const row = $(this).closest('tr');
                calculateRowTotal(row);
                calculateGrandTotals();
            });

            // ============================================
            // 12. PRODUCT SELECT - LOAD VARIATIONS
            // ============================================
            $(document).on('change', '.product-select', function() {
                const row = $(this).closest('tr');
                const productId = $(this).val();
                const variationSelect = row.find('.variation-select');

                variationSelect.html('<option value="">--Select Variation--</option>');
                row.find('.conversion-select').html(
                    '<option value="" data-conversion-factor="1" data-to-unit-id="" data-to-unit-name="N/A">--Select Conversion--</option>'
                );
                row.find('.selected-unit-id').val('');
                row.find('.selected-unit-name').text('N/A');
                row.find('.price-input').val(decimal(0));
                row.find('.conversion-factor').val(decimal(1));

                if (!productId) {
                    calculateRowTotal(row);
                    calculateGrandTotals();
                    return;
                }

                $.ajax({
                    url: url_local + '/admin/product/variation-by-product/' + productId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        const variations = response.Data || [];
                        let options = '<option value="">--Select Variation--</option>';

                        if (variations.length === 0) {
                            options += `
                                    <option value="" data-unit-id="" data-unit-name="N/A" data-price="0" data-requested-qty="0">
                                        No Variation Available
                                    </option>
                                `;
                        } else {
                            $.each(variations, function(i, variation) {
                                options += `
                                        <option value="${variation.product_variation_id}"
                                            data-unit-id="${variation.purchase_unit?.unit_id || ''}"
                                            data-unit-name="${variation.purchase_unit?.name || 'N/A'}"
                                            data-price="${variation.purchase_price || 0}"
                                            data-requested-qty="0">
                                            ${variation.name}
                                        </option>
                                    `;
                            });
                        }

                        variationSelect.html(options);
                        if (variations.length > 0) {
                            variationSelect.find('option:eq(1)').prop('selected', true);
                            variationSelect.trigger('change');
                        }
                    },
                    error: function(xhr) {
                        errorMessage('Failed to load product variations.');
                    }
                });
            });

            // ============================================
            // 13. VARIATION SELECT - LOAD CONVERSIONS
            // ============================================
            $(document).on('change', '.variation-select', function() {
                const row = $(this).closest('tr');
                const variationId = $(this).val();
                const selectedOption = $(this).find(':selected');

                const unitId = selectedOption.data('unit-id') || '';
                const unitName = selectedOption.data('unit-name') || 'N/A';
                const price = selectedOption.data('price') || 0;
                const requestedQty = selectedOption.data('requested-qty') || 0;

                const conversionSelect = row.find('.conversion-select');
                let options =
                    '<option value="" data-conversion-factor="1" data-to-unit-id="" data-to-unit-name="N/A">--Select Conversion--</option>';

                row.find('.selected-unit-id').val(unitId);
                row.find('.selected-unit-name').text(unitName);
                row.find('.price-input').val(decimal(price));
                row.find('.conversion-factor').val(decimal(1));

                if ($('#purchase_type').val() === 'purchase_request' && requestedQty > 0) {
                    const qtyInput = row.find('.quantity-input');
                    qtyInput.data('requested-qty', requestedQty);
                    qtyInput.attr('max', requestedQty);
                    qtyInput.val(requestedQty);
                    row.find('.input-group-text small').text('Max: ' + requestedQty);
                    $('#maxQtyHint').text(requestedQty);
                }

                if (!variationId) {
                    calculateRowTotal(row);
                    calculateGrandTotals();
                    return;
                }

                $.ajax({
                    url: url_local + '/admin/product-variation-unit-conversion/by-variation/' +
                        variationId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        const conversions = response.Data || [];

                        if (conversions.length === 0) {
                            options += `
                                    <option value="" data-conversion-factor="1" data-to-unit-id="" data-to-unit-name="N/A">
                                        No Conversion Available
                                    </option>
                                `;
                        } else {
                            $.each(conversions, function(i, conversion) {
                                options += `
                                        <option value="${conversion.product_variation_unit_conversion_id}"
                                            data-from-unit-id="${conversion.from_unit_id || ''}"
                                            data-from-unit-name="${conversion.from_unit?.name || 'N/A'}"
                                            data-to-unit-id="${conversion.to_unit_id || ''}"
                                            data-to-unit-name="${conversion.to_unit?.name || 'N/A'}"
                                            data-conversion-factor="${decimal(conversion.conversion_factor)}">
                                            ${conversion.from_unit?.name || 'N/A'} to ${conversion.to_unit?.name || 'N/A'} 
                                            (${decimal(conversion.conversion_factor)})
                                        </option>
                                    `;
                            });
                        }

                        conversionSelect.html(options);
                        if (conversions.length > 0) {
                            conversionSelect.find('option:eq(1)').prop('selected', true);
                            conversionSelect.trigger('change');
                        }
                    },
                    error: function(xhr) {
                        errorMessage('Failed to load unit conversions.');
                    }
                });

                calculateRowTotal(row);
                calculateGrandTotals();
            });

            // ============================================
            // 14. CONVERSION SELECT - UPDATE UNIT & FACTOR
            // ============================================
            $(document).on('change', '.conversion-select', function() {
                const row = $(this).closest('tr');
                const selectedOption = $(this).find(':selected');
                const conversionFactor = parseFloat(selectedOption.data('conversion-factor')) || 1;
                const toUnitId = selectedOption.data('to-unit-id') || '';
                const toUnitName = selectedOption.data('to-unit-name') || 'N/A';

                row.find('.selected-unit-id').val(toUnitId);
                row.find('.selected-unit-name').text(toUnitName);
                row.find('.conversion-factor').val(conversionFactor.toFixed(decimal_points || 2));

                calculateRowTotal(row);
                calculateGrandTotals();
            });

            // ============================================
            // 15. QUANTITY INPUT VALIDATION
            // ============================================
            $(document).on('input change', '.quantity-input', function() {
                const row = $(this).closest('tr');
                const requestedQty = parseFloat($(this).data('requested-qty')) || 0;
                const currentQty = parseFloat($(this).val()) || 0;

                if ($('#purchase_type').val() === 'purchase_request' && requestedQty > 0) {
                    if (currentQty > requestedQty) {
                        $(this).val(requestedQty);
                        errorMessage('Quantity cannot exceed requested quantity: ' + requestedQty);
                        $(this).addClass('is-invalid');
                        setTimeout(() => $(this).removeClass('is-invalid'), 3000);
                    } else if (currentQty < 0) {
                        $(this).val(0);
                        errorMessage('Quantity cannot be negative');
                    }

                    const rejectedQty = requestedQty - parseFloat($(this).val()) || 0;
                    if (rejectedQty > 0) {
                        row.find('.rejected-info').remove();
                        row.find('.quantity-input').closest('td').append(`
                                <span class="rejected-info text-warning small d-block">
                                    <i class="fa fa-exclamation-triangle"></i> Rejected: ${rejectedQty} ${row.find('.selected-unit-name').text() || 'units'}
                                </span>
                            `);
                        const rejectedInput = row.find('.rejected-quantity-input');
                        if (rejectedInput.length) {
                            rejectedInput.val(rejectedQty);
                        }
                    } else {
                        row.find('.rejected-info').remove();
                        const rejectedInput = row.find('.rejected-quantity-input');
                        if (rejectedInput.length) {
                            rejectedInput.val(0);
                        }
                    }
                }

                calculateRowTotal(row);
                calculateGrandTotals();
            });

            // ============================================
            // 16. QUANTITY/PRICE INPUT CHANGES
            // ============================================
            $(document).on('input change', '.quantity-input, .price-input', function() {
                const row = $(this).closest('tr');
                calculateRowTotal(row);
                calculateGrandTotals();
            });

            // ============================================
            // 17. MASTER DISCOUNT, TAX, SHIPPING CHANGES
            // ============================================
            $(document).on('input change', '.discount-input, .tax-input, .shipping-input', function() {
                calculateGrandTotals();
            });

            // ============================================
            // 18. BUSINESS SELECTION
            // ============================================
            $('#business_id').on('change', function() {
                const businessId = $(this).val();

                $('#supplier_id').html('<option value="">--Select Supplier--</option>').val('').trigger(
                    'change');
                $('#warehouse_id').html('<option value="">--Select Warehouse--</option>').val('').trigger(
                    'change');

                if (!businessId) return;

                $.ajax({
                    url: url_local + '/admin/supplier/by-business/' + businessId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        let options = '<option value="">--Select Supplier--</option>';
                        if (response.Data && response.Data.length) {
                            $.each(response.Data, function(i, item) {
                                options += `
                                        <option value="${item.supplier_id}">
                                            ${item.code} ${item.name}
                                        </option>
                                    `;
                            });
                        }
                        $('#supplier_id').html(options);
                    },
                    error: function() {
                        errorMessage('Failed to load suppliers.');
                    }
                });

                $.ajax({
                    url: url_local + '/admin/warehouse/by-business/' + businessId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        let options = '<option value="">--Select Warehouse--</option>';
                        if (response.Data && response.Data.length) {
                            $.each(response.Data, function(i, item) {
                                options += `
                                        <option value="${item.warehouse_id}">
                                            ${item.name}
                                        </option>
                                    `;
                            });
                        }
                        $('#warehouse_id').html(options);
                    },
                    error: function() {
                        errorMessage('Failed to load warehouses.');
                    }
                });

                $.ajax({
                    url: url_local + '/admin/product/by-business/' + businessId,
                    type: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        window.productsData = response.Data || [];
                        const productOptions = buildProductOptions(window.productsData);
                        $('.product-select').each(function() {
                            const currentVal = $(this).val();
                            $(this).html(productOptions);
                            if (currentVal) {
                                $(this).val(currentVal);
                            }
                        });
                    },
                    error: function() {
                        errorMessage('Failed to load products.');
                    }
                });
            });

            // ============================================
            // 19. HELPER: BUILD PRODUCT OPTIONS
            // ============================================
            function buildProductOptions(products) {
                let options = '<option value="">--Select Product--</option>';
                if (products && products.length) {
                    $.each(products, function(i, product) {
                        options += `
                                <option value="${product.product_id}">${product.name}</option>
                            `;
                    });
                }
                return options;
            }

            // ============================================
            // 20. NUMBER VALIDATION
            // ============================================
            window.isNumberKey = function(evt) {
                const charCode = evt.which ? evt.which : evt.keyCode;
                if ([8, 9, 13, 27, 46, 110, 190].indexOf(charCode) !== -1 ||
                    (charCode === 65 && evt.ctrlKey === true) ||
                    (charCode === 67 && evt.ctrlKey === true) ||
                    (charCode === 86 && evt.ctrlKey === true) ||
                    (charCode === 88 && evt.ctrlKey === true) ||
                    (charCode >= 35 && charCode <= 39)) {
                    return true;
                }
                if ((charCode < 48 || charCode > 57) && charCode !== 46) {
                    return false;
                }
                return true;
            };

            // ============================================
            // 21. DECIMAL HELPER
            // ============================================
            window.decimal = function(value) {
                if (value === null || value === undefined || isNaN(value)) {
                    return (0).toFixed(window.decimal_points || 2);
                }
                return parseFloat(value).toFixed(window.decimal_points || 2);
            };

            // ============================================
            // 23. KEYBOARD SHORTCUTS
            // ============================================
            $(document).on('keydown', '.quantity-input, .price-input', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const type = $('#purchase_type').val();
                    if (type === 'direct') {
                        addProductRow();
                    }
                }
            });

            // ============================================
            // 24. INITIAL SETUP
            // ============================================
            window.productsData = @json($products ?? []);
            window.decimal_points = @json($decimal_points ?? 2);

            const initialType = $('#purchase_type').val();
            if (initialType === 'direct') {
                $('.purchase-request-section').addClass('d-none');
                $('#addProductBtn').show();
                $('#qtyHint').text('(Enter quantity)');
                if ($('#productRows tr').length === 0) {
                    addProductRow();
                }
            } else {
                $('.purchase-request-section').removeClass('d-none');
                $('#addProductBtn').hide();
                $('#qtyHint').html('(Max: <span id="maxQtyHint">0</span>)');
                if ($('#productRows tr').length === 0) {
                    $('#productRows').html(
                        '<tr><td colspan="13" class="text-center text-muted">Select a purchase request to load products</td></tr>'
                    );
                }
            }

            setTimeout(function() {
                $('#productRows tr').each(function() {
                    calculateRowTotal($(this));
                });
                calculateGrandTotals();
            }, 100);

            // ============================================
            // 25. CSS STYLES
            // ============================================
            if (!$('#purchase-custom-styles').length) {
                $('head').append(`
                        <style id="purchase-custom-styles">
                            .shake {
                                animation: shake 0.5s ease-in-out;
                            }
                            @keyframes shake {
                                0%, 100% { transform: translateX(0); }
                                25% { transform: translateX(-10px); }
                                75% { transform: translateX(10px); }
                            }
                            .row-total, #subtotal, #total {
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
                            .form-control-plaintext {
                                padding-top: .375rem;
                                padding-bottom: .375rem;
                            }
                            .table td {
                                vertical-align: middle;
                            }
                            .purchase-request-section {
                                transition: all 0.3s ease;
                            }
                            .is-invalid {
                                border-color: #dc3545 !important;
                            }
                            .rejected-info {
                                font-size: 0.8rem;
                                margin-top: 0.25rem;
                            }
                            .bg-light {
                                background-color: #f8f9fa !important;
                            }
                            .input-group-text small {
                                font-size: 0.7rem;
                            }
                            .row-discount-input, .row-tax-input {
                                max-width: 80px;
                            }
                            .row-subtotal, .row-discount-amount, .row-tax-amount, .row-total {
                                background-color: #f8f9fa;
                            }
                        </style>
                    `);
            }
        });

        // ============================================
        // 26. FORM VALIDATION BEFORE SUBMIT
        // ============================================
        $(document).on('submit', 'form[action*="/admin/purchase"]', function(e) {
            if ($('#productRows tr').length === 0 ||
                $('#productRows tr td.text-muted').length > 0) {
                e.preventDefault();
                errorMessage('Please add at least one product.');
                return false;
            }

            let isValid = true;
            let errorMessages = [];

            $('#productRows tr').each(function(index) {
                const productSelect = $(this).find('.product-select');
                const variationSelect = $(this).find('.variation-select');
                const quantity = $(this).find('.quantity-input').val();
                const price = $(this).find('.price-input').val();

                $(this).find('.is-invalid').removeClass('is-invalid');

                if (!productSelect.val()) {
                    isValid = false;
                    productSelect.addClass('is-invalid');
                    errorMessages.push(`Row ${index + 1}: Please select a product`);
                    return false;
                }

                if (!variationSelect.val()) {
                    isValid = false;
                    variationSelect.addClass('is-invalid');
                    errorMessages.push(`Row ${index + 1}: Please select a variation`);
                    return false;
                }

                if (!quantity || parseFloat(quantity) < 0) {
                    isValid = false;
                    $(this).find('.quantity-input').addClass('is-invalid');
                    errorMessages.push(`Row ${index + 1}: Please enter a valid quantity`);
                    return false;
                }

                if (price === '' || parseFloat(price) < 0) {
                    isValid = false;
                    $(this).find('.price-input').addClass('is-invalid');
                    errorMessages.push(`Row ${index + 1}: Please enter a valid price`);
                    return false;
                }

                if ($('#purchase_type').val() === 'purchase_request') {
                    const requestedQty = parseFloat($(this).find('.quantity-input').data('requested-qty')) || 0;
                    const currentQty = parseFloat(quantity) || 0;
                    if (requestedQty > 0 && currentQty > requestedQty) {
                        isValid = false;
                        $(this).find('.quantity-input').addClass('is-invalid');
                        errorMessages.push(
                            `Row ${index + 1}: Quantity (${currentQty}) exceeds requested quantity (${requestedQty})`
                            );
                        return false;
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                errorMessage(errorMessages.join('\n') || 'Please fill all required fields correctly.');
                $('.is-invalid:first').focus();
                return false;
            }

            if (!$('#supplier_id').val()) {
                e.preventDefault();
                $('#supplier_id').addClass('is-invalid');
                errorMessage('Please select a supplier.');
                return false;
            }

            if (!$('#warehouse_id').val()) {
                e.preventDefault();
                $('#warehouse_id').addClass('is-invalid');
                errorMessage('Please select a warehouse.');
                return false;
            }

            if (!$('input[name="purchase_date"]').val()) {
                e.preventDefault();
                $('input[name="purchase_date"]').addClass('is-invalid');
                errorMessage('Please select a purchase date.');
                return false;
            }

            if (!$('input[name="expected_delivery_date"]').val()) {
                e.preventDefault();
                $('input[name="expected_delivery_date"]').addClass('is-invalid');
                errorMessage('Please select a delivery date.');
                return false;
            }

            return true;
        });

        $(document).on('input change', '.is-invalid', function() {
            $(this).removeClass('is-invalid');
        });
    </script>
@endsection