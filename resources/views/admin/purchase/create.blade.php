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
            <form action="{{ isset($purchase) ? url('admin/purchase/' . $purchase->purchase_id) : url('admin/purchase') }}"
                method="POST">
                @csrf
                @if (isset($purchase))
                    @method('PUT')
                @endif
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
                                    {{ old('purchase_type', $purchase->purchase_type ?? '') == 'purchase' ? 'selected' : '' }}>
                                    Purchase From Purchase Order
                                </option>
                            </select>
                        </div>
                        <div
                            class="col-md-3 purchase-order-section {{ old('purchase_type', $purchase->purchase_type ?? 'direct') == 'purchase' ? '' : 'd-none' }}">
                            <label class="fw-semibold">Purchase Order<span class="text-danger">*</span></label>
                            <select class="form-select" name="purchase_order_id" id="purchase_order_id">
                                <option value="">-- Select Purchase Order --</option>
                                @foreach ($purchase_orders as $item)
                                    <option value="{{ $item->purchase_order_id }}"
                                        {{ old('purchase_order_id', $purchase->purchase_order_id ?? '') == $item->purchase_order_id ? 'selected' : '' }}>
                                        {{ $item->purchase_order_no }}
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
                            <label class="fw-semibold">Purchase Number</label>
                            <input type="text" class="form-control" name="purchase_no"
                                value="{{ $purchase->purchase_no ?? ($purchase_no ?? 'Auto Generated') }}" readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">Purchase Date <span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" name="purchase_date"
                                value="{{ old('purchase_date', isset($purchase) ? localDate($purchase->purchase_date) : localDate(date('Y-m-d'))) }}"
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
                                            <th>Product</th>
                                            <th>Variation</th>
                                            <th>Conversion</th>
                                            <th>Unit</th>
                                            <th>Ordered Qty</th>
                                            <th>Unit Price</th>
                                            <th class="currency-label">Total</th>
                                            <th>Action</th>
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
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][ordered_quantity]"
                                                            class="form-control quantity-input"
                                                            onkeypress="return isNumberKey(event)"
                                                            value="{{ decimal($detail->ordered_quantity) }}" required>
                                                        <input type="hidden"
                                                            name="products[{{ $loop->index }}][conversion_factor]"
                                                            class="conversion-factor"
                                                            value="{{ $detail->conversion_factor ?? 1 }}">
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                            name="products[{{ $loop->index }}][unit_price]"
                                                            class="form-control price-input"
                                                            onkeypress="return isNumberKey(event)"
                                                            value="{{ decimal($detail->unit_price) }}" required>
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
                                            <td colspan="5" class="text-end"><strong
                                                    class="currency-label">Subtotal</strong></td>
                                            <td colspan="2">
                                                <input type="text" name="subtotal" id="subtotal"
                                                    class="form-control"
                                                    value="{{ old('subtotal', decimal($purchase->subtotal ?? 0)) }}"
                                                    readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Discount (%)</strong></td>
                                            <td colspan="2">
                                                <input type="text" name="discount" id="discount"
                                                    class="form-control discount-input"
                                                    onkeypress="return isNumberKey(event)"
                                                    value="{{ old('discount', decimal($purchase->discount ?? 0)) }}">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong class="currency-label">Discount
                                                    Amount</strong></td>
                                            <td colspan="2">
                                                <input type="text" name="discount_amount" id="discount_amount"
                                                    class="form-control"
                                                    value="{{ old('discount_amount', decimal($purchase->discount_amount ?? 0)) }}"
                                                    readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong>Tax (%)</strong></td>
                                            <td colspan="2">
                                                <input type="text" name="tax" id="tax"
                                                    class="form-control tax-input" onkeypress="return isNumberKey(event)"
                                                    value="{{ old('tax', decimal($purchase->tax ?? 0)) }}">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong class="currency-label">Tax
                                                    Amount</strong></td>
                                            <td colspan="2">
                                                <input type="text" name="tax_amount" id="tax_amount"
                                                    class="form-control"
                                                    value="{{ old('tax_amount', decimal($purchase->tax_amount ?? 0)) }}"
                                                    readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong class="currency-label">Shipping
                                                    Charge</strong></td>
                                            <td colspan="2">
                                                <input type="text" name="shipping_charge"
                                                    onkeypress="return isNumberKey(event)" id="shipping_charge"
                                                    class="form-control shipping-input"
                                                    value="{{ old('shipping_charge', decimal($purchase->shipping_charge ?? 0)) }}">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5" class="text-end"><strong class="currency-label">Grand
                                                    Total</strong></td>
                                            <td colspan="2">
                                                <input type="text" name="total" id="grand_total"
                                                    class="form-control"
                                                    value="{{ old('total', decimal($purchase->total ?? 0)) }}" readonly>
                                            </td>
                                            <td></td>
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
        // PURCHASE TYPE TOGGLE
        // ============================================
        $('#purchase_type').change(function() {
            let type = $(this).val();
            if (type == 'direct') {
                $('.purchase-order-section').addClass('d-none');
                $('#addProductBtn').show();
                $('#productRows').empty();
                calculateGrandTotals();
            } else {
                $('.purchase-order-section').removeClass('d-none');
                $('#addProductBtn').hide();
                $('#productRows').empty();
                calculateGrandTotals();
            }
        });

        // ============================================
        // VARIABLE DECLARATIONS
        // ============================================
        var productIndex = {{ isset($purchase) ? $purchase->purchaseDetails->count() : 0 }};

        // ============================================
        // PURCHASE ORDER SELECTION
        // ============================================
        $('#purchase_order_id').change(function() {
            let id = $(this).val();
            if (!id) {
                $('#productRows').empty();
                calculateGrandTotals();
                return;
            }

            // Show loading state
            $('#productRows').html(
                '<tr><td colspan="8" class="text-center">Loading purchase order details...</td></tr>');

            $.ajax({
                url: url_local + '/admin/purchase-order/details/' + id,
                type: 'GET',
                success: function(response) {
                    console.log('Purchase Order Response:', response);
                    if (response.Success) {
                        loadPurchaseOrder(response.Data);
                    } else {
                        errorMessage(response.Message || 'Failed to load purchase order details.');
                    }
                },
                error: function(xhr) {
                    console.error('Error loading purchase order:', xhr);
                    errorMessage('Failed to load purchase order details. Please try again.');
                }
            });
        });

        // ============================================
        // FUNCTION: LOAD PURCHASE ORDER
        // ============================================
        function loadPurchaseOrder(data) {
            try {
                // Set header fields
                if (data.header) {
                    $('#supplier_id').val(data.header.supplier_id).trigger('change');
                    $('#warehouse_id').val(data.header.warehouse_id).trigger('change');
                    if ($('#branch_id').length) {
                        $('#branch_id').val(data.header.branch_id).trigger('change');
                    }
                    $('#discount').val(data.header.discount || 0);
                    $('#tax').val(data.header.tax || 0);
                    $('#shipping_charge').val(data.header.shipping_charge || 0);
                    $('textarea[name=description]').val(data.header.description || '');
                }

                // Clear existing rows
                $('#productRows').empty();
                productIndex = 0;

                // Add each product detail
                if (data.details && data.details.length > 0) {
                    $.each(data.details, function(i, item) {
                        addPurchaseOrderRow(item);
                    });
                } else {
                    $('#productRows').html(
                        '<tr><td colspan="8" class="text-center text-muted">No products found in this purchase order.</td></tr>'
                    );
                }

                // Recalculate totals after all rows are added
                setTimeout(function() {
                    calculateGrandTotals();
                }, 500);

            } catch (error) {
                console.error('Error in loadPurchaseOrder:', error);
                errorMessage('Error loading purchase order data.');
            }
        }

        // ============================================
        // FUNCTION: ADD PURCHASE ORDER ROW
        // ============================================
        function addPurchaseOrderRow(item) {

            const products = @json($products);

            let productOptions = '<option value="">--Select Product--</option>';

            $.each(products, function(i, product) {

                productOptions += `
            <option value="${product.product_id}"
                ${product.product_id == item.product_id ? 'selected' : ''}>
                ${product.name}
            </option>
        `;

            });



            //=========================
            // Variations
            //=========================

            let variationOptions =
                '<option value="">--Select Variation--</option>';

            if (item.productVariations && item.productVariations.length) {

                $.each(item.productVariations, function(i, variation) {

                    variationOptions += `
                <option
                    value="${variation.product_variation_id}"
                    data-unit-id="${item.unit_id}"
                    data-unit-name="${item.unit_name}"
                    data-price="${item.unit_price}"
                    ${variation.product_variation_id == item.product_variation_id ? 'selected' : ''}>
                    ${variation.name}
                </option>
            `;

                });

            }



            //=========================
            // Conversions
            //=========================

            let conversionOptions =
                '<option value="">--Select Conversion--</option>';

            if (item.conversions && item.conversions.length) {

                $.each(item.conversions, function(i, conversion) {

                    conversionOptions += `
                <option
                    value="${conversion.product_variation_unit_conversion_id}"
                    data-conversion-factor="${conversion.conversion_factor}"
                    data-to-unit-id="${conversion.to_unit_id}"
                    data-to-unit-name="${conversion.to_unit_name}"
                    ${conversion.product_variation_unit_conversion_id == item.product_variation_unit_conversion_id ? 'selected' : ''}>
                    ${conversion.from_unit_name}
                    →
                    ${conversion.to_unit_name}
                    (${conversion.conversion_factor})
                </option>
            `;

                });

            }



            $('#productRows').append(`

        <tr>

            <td>

                <select
                    name="products[${productIndex}][product_id]"
                    class="form-select product-select">

                    ${productOptions}

                </select>

            </td>

            <td>

                <select
                    name="products[${productIndex}][product_variation_id]"
                    class="form-select variation-select">

                    ${variationOptions}

                </select>

            </td>

            <td>

                <select
                    name="products[${productIndex}][product_variation_unit_conversion_id]"
                    class="form-select conversion-select">

                    ${conversionOptions}

                </select>

            </td>

            <td>

                <input
                    type="hidden"
                    class="selected-unit-id"
                    name="products[${productIndex}][unit_id]"
                    value="${item.unit_id}">

                <span class="selected-unit-name">
                    ${item.unit_name}
                </span>

            </td>

            <td>

                <input
                    type="text"
                    class="form-control quantity-input"
                    name="products[${productIndex}][ordered_quantity]"
                    value="${item.ordered_quantity}">

                <input
                    type="hidden"
                    class="conversion-factor"
                    name="products[${productIndex}][conversion_factor]"
                    value="${item.conversion_factor}">

            </td>

            <td>

                <input
                    type="text"
                    class="form-control price-input"
                    name="products[${productIndex}][unit_price]"
                    value="${item.unit_price}">

            </td>

            <td>

                <input
                    type="text"
                    class="form-control row-total"
                    name="products[${productIndex}][total]"
                    value="${item.total}"
                    readonly>

            </td>

            <td>

                <a href="javascript:void(0)"
                    onclick="removeRow(this)"
                    class="text-danger">

                    <i class="fa fa-trash"></i>

                </a>

            </td>

        </tr>

    `);

            const row = $('#productRows tr:last');

            // Unit
            row.find('.selected-unit-id').val(item.unit_id);
            row.find('.selected-unit-name').text(item.unit_name);

            // Qty
            row.find('.quantity-input').val(item.ordered_quantity);

            // Price
            row.find('.price-input').val(item.unit_price);

            // Conversion
            row.find('.conversion-factor').val(item.conversion_factor);

            // Total
            row.find('.row-total').val(item.total);

            productIndex++;

            calculateRowTotal(row);

            calculateGrandTotals();

        }

        // ============================================
        // DOCUMENT READY
        // ============================================
        $(document).ready(function() {
            // Initialize Select2
            $('#business_id, #supplier_id, #warehouse_id').select2();

            // ============================================
            // 1. REAL-TIME ROW CALCULATION
            // ============================================
            $(document).on('input change', '.quantity-input, .price-input', function() {
                calculateRowTotal($(this).closest('tr'));
                calculateGrandTotals();
            });

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
                row.find('.price-input').val(decimal(0));
                row.find('.quantity-input').val(decimal(1));
                row.find('.conversion-factor').val(decimal(1));

                if (!productId) {
                    calculateRowTotal(row);
                    calculateGrandTotals();
                    return;
                }

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
                                        data-unit-name="${variation.purchase_unit?.name ?? 'N/A'}"
                                        data-price="${variation.purchase_price ?? 0}">
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

                // Reset conversion dropdown
                const conversionSelect = row.find('.conversion-select');
                let options =
                    '<option value="" data-conversion-factor="1" data-to-unit-id="" data-to-unit-name="N/A">--Select Conversion--</option>';

                // Update unit
                row.find('.selected-unit-id').val(unitId);
                row.find('.selected-unit-name').text(unitName);

                // Update price
                row.find('.price-input').val(decimal(price));
                row.find('.conversion-factor').val(decimal(1));

                if (!variationId) {
                    calculateRowTotal(row);
                    calculateGrandTotals();
                    return;
                }

                // AJAX to fetch unit conversions
                $.ajax({
                    url: "{{ url('admin/product-variation-unit-conversion/by-variation') }}/" +
                        variationId,
                    type: "GET",
                    success: function(response) {
                        let conversions = response.Data || [];
                        if (conversions.length === 0) {
                            options += `
                                <option value="" data-conversion-factor="1" data-to-unit-id="" data-to-unit-name="N/A">
                                    No Conversion Available
                                </option>
                            `;
                        } else {
                            $.each(conversions, function(i, conversion) {
                                console.log(conversion);
                                options += `
                                    <option 
                                        value="${conversion.product_variation_unit_conversion_id}"
                                        data-from-unit-id="${conversion.from_unit_id}"
                                        data-from-unit-name="${conversion.from_unit?.name || 'N/A'}"
                                        data-to-unit-id="${conversion.to_unit_id}"
                                        data-to-unit-name="${conversion.to_unit?.name || 'N/A'}"
                                        data-conversion-factor="${decimal(conversion.conversion_factor)}">
                                        ${conversion.from_unit?.name || 'N/A'} to ${conversion.to_unit?.name || 'N/A'} 
                                        (${decimal(conversion.conversion_factor)})
                                    </option>
                                `;
                            });
                        }
                        conversionSelect.html(options);
                        // Trigger change to load first conversion's data
                        conversionSelect.trigger('change');
                    },
                    error: function() {
                        errorMessage('Failed to load unit conversions.');
                    }
                });

                // Calculate row total
                calculateRowTotal(row);
                calculateGrandTotals();
            });

            // ============================================
            // 4. CONVERSION SELECTION - UPDATE UNIT & FACTOR
            // ============================================
            $(document).on('change', '.conversion-select', function() {
                const row = $(this).closest('tr');
                const selectedOption = $(this).find(':selected');
                const conversionFactor = parseFloat(selectedOption.data('conversion-factor')) || 1;
                const toUnitId = selectedOption.data('to-unit-id') || '';
                const toUnitName = selectedOption.data('to-unit-name') || 'N/A';

                // Update unit to "to_unit"
                row.find('.selected-unit-id').val(toUnitId);
                row.find('.selected-unit-name').text(toUnitName);

                // Update conversion factor
                row.find('.conversion-factor').val(conversionFactor.toFixed(decimal_points));

                // Calculate row total
                calculateRowTotal(row);
                calculateGrandTotals();
            });

            // ============================================
            // 5. DISCOUNT, TAX, SHIPPING CHANGES
            // ============================================
            $(document).on('input change', '.discount-input, .tax-input, .shipping-input', function() {
                calculateGrandTotals();
            });

            // ============================================
            // 6. INITIAL CALCULATIONS
            // ============================================
            $('#productRows tr').each(function() {
                calculateRowTotal($(this));
            });
            calculateGrandTotals();

            // ============================================
            // 7. KEYBOARD SHORTCUTS (Enter to add row)
            // ============================================
            $(document).on('keydown', '.quantity-input, .price-input', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if ($('#purchase_type').val() == 'direct') {
                        addProductRow();
                    }
                }
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
        // 9. FUNCTION: CALCULATE ROW TOTAL
        // ============================================
        function calculateRowTotal(row) {
            if (!row || !row.length) return 0;

            const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
            const price = parseFloat(row.find('.price-input').val()) || 0;
            const conversionFactor = parseFloat(row.find('.conversion-factor').val()) || 1;

            // Apply conversion factor to quantity
            const convertedQuantity = quantity * conversionFactor;
            const total = convertedQuantity * price;

            // Update row total with 2 decimal places
            row.find('.row-total').val(total.toFixed(decimal_points));

            // Highlight if total is negative or zero
            const totalInput = row.find('.row-total');
            if (total < 0) {
                totalInput.css('color', 'red');
            } else if (total === 0) {
                totalInput.css('color', '#6c757d');
            } else {
                totalInput.css('color', '#198754');
            }

            return total;
        }

        // ============================================
        // 10. FUNCTION: CALCULATE GRAND TOTALS
        // ============================================
        function calculateGrandTotals() {
            let subtotal = 0;

            $('#productRows tr').each(function() {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const price = parseFloat($(this).find('.price-input').val()) || 0;
                const conversionFactor = parseFloat($(this).find('.conversion-factor').val()) || 1;
                const convertedQuantity = quantity * conversionFactor;
                const total = convertedQuantity * price;
                subtotal += total;
            });

            const discountPercent = parseFloat($('#discount').val()) || 0;
            const taxPercent = parseFloat($('#tax').val()) || 0;
            const shippingCharge = parseFloat($('#shipping_charge').val()) || 0;

            if (discountPercent > 100) {
                $('#discount').val(100);
                errorMessage('Discount cannot exceed 100%');
                return;
            }

            const discountAmount = (subtotal * discountPercent) / 100;
            const afterDiscount = subtotal - discountAmount;
            const taxAmount = (afterDiscount * taxPercent) / 100;
            const afterTax = afterDiscount + taxAmount;
            const grandTotal = afterTax + shippingCharge;

            $('#subtotal').val(subtotal.toFixed(decimal_points));
            $('#discount_amount').val(discountAmount.toFixed(decimal_points));
            $('#tax_amount').val(taxAmount.toFixed(decimal_points));
            $('#grand_total').val(grandTotal.toFixed(decimal_points));

            // Color coding
            const grandTotalInput = $('#grand_total');
            if (grandTotal < 0) {
                grandTotalInput.css('color', 'red');
            } else if (grandTotal === 0) {
                grandTotalInput.css('color', '#6c757d');
            } else {
                grandTotalInput.css('color', '#198754');
            }

            // Show/hide rows
            if (discountPercent > 0) {
                $('#discount_amount').closest('tr').show();
            } else {
                $('#discount_amount').closest('tr').hide();
            }

            if (taxPercent > 0) {
                $('#tax_amount').closest('tr').show();
            } else {
                $('#tax_amount').closest('tr').hide();
            }

            return grandTotal;
        }

        // ============================================
        // 11. FUNCTION: ADD PRODUCT ROW (Direct Purchase)
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
                               class="form-control quantity-input" value="1" onkeypress="return isNumberKey(event)" required>
                        <input type="hidden" name="products[${productIndex}][conversion_factor]" class="conversion-factor" value="1">
                    </td>
                    <td>
                        <input type="text" name="products[${productIndex}][unit_price]" 
                               class="form-control price-input" value="0" onkeypress="return isNumberKey(event)" required>
                    </td>
                    <td>
                        <input type="text" name="products[${productIndex}][total]" class="form-control row-total" value="0.00" readonly>
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
        }

        // ============================================
        // 12. FUNCTION: REMOVE ROW
        // ============================================
        function removeRow(button) {
            if ($('#productRows tr').length > 1) {
                const row = $(button).closest('tr');
                row.fadeOut(300, function() {
                    $(this).remove();
                    calculateGrandTotals();
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
        // 13. FUNCTION: ERROR MESSAGE
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
        // 14. FUNCTION: DECIMAL HELPER
        // ============================================
        if (typeof decimal !== 'function') {
            function decimal(value) {
                if (value === null || value === undefined || isNaN(value)) {
                    return (0).toFixed(decimal_points || 2);
                }
                return parseFloat(value).toFixed(decimal_points || 2);
            }
        }

        // ============================================
        // 15. CSS STYLES
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

        // ============================================
        // 16. BUSINESS SELECTION
        // ============================================
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
