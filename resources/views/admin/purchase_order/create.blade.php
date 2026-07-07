@php
    use App\Enums\RoleNames;
    use Carbon\Carbon;
@endphp
@extends('layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($purchase_order) ? 'Update' : 'New' }} Purchase Order</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($purchase_order) ? 'Update' : 'Create' }} Purchase Order</h5>
            </div>
            <form
                action="{{url('admin/purchase-order') }}"
                method="POST">
                @csrf
                <input type="hidden" name="purchase_order_id"
                    value="{{ isset($purchase_order) ? $purchase_order->purchase_order_id : '' }}">
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
                                            {{ old('business_id', $purchase_order->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
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
                                            {{ old('supplier_id', $purchase_order->supplier_id ?? '') == $item->supplier_id ? 'selected' : '' }}>
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
                                            {{ old('warehouse_id', $purchase_order->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">PO Number</label>
                            <input type="text" class="form-control" name="purchase_order_no"
                                value="{{ $purchase_order->purchase_order_no ?? ($purchase_order_no ?? 'Auto Generated') }}"
                                readonly>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">PO Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="purchase_order_date"
                                value="{{ old('purchase_order_date', Carbon::parse($purchase_order->purchase_order_date)->format('Y-m-d') ?? date('Y-m-d')) }}"
                                required>
                        </div>

                        <div class="col-md-3">
                            <label class="fw-semibold">Expected Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="purchase_expected_date"
                                value="{{ old('purchase_expected_date', Carbon::parse($purchase_order->purchase_expected_date)->format('Y-m-d') ?? date('Y-m-d', strtotime('+7 days'))) }}"
                                required>
                        </div>
                        <div class="col-md-9">
                            <label class="fw-semibold">Description</label>
                            <textarea class="form-control" name="description">{{ old('description', $purchase_order->description ?? '') }}</textarea>
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
                                            <th style="min-width: 180px;">Product</th>
                                            <th style="min-width: 180px;">Variation</th>
                                            <th style="min-width: 160px;">Conversion</th>
                                            <th style="min-width: 80px;">Unit</th>
                                            <th style="min-width: 100px;">Quantity</th>
                                            <th style="min-width: 120px;">Unit Price</th>
                                            <th style="min-width: 120px;">Total</th>
                                            <th style="min-width: 10px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productRows">
                                        @if (isset($purchase_order) && $purchase_order->purchaseOrderDetails->count() > 0)
                                            @foreach ($purchase_order->purchaseOrderDetails as $detail)
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
                                                            value="{{ $detail->ordered_quantity }}" required>
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
                                                            value="{{ $detail->unit_price }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control row-total"
                                                            name="products[{{ $loop->index }}][total]"
                                                            value="{{ number_format($detail->total, 2) }}" readonly>
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
                                            <td colspan="6" class="text-end"><strong>Subtotal</strong></td>
                                            <td>
                                                <input type="text" name="subtotal" id="subtotal"
                                                    class="form-control"
                                                    value="{{ old('subtotal', $purchase_order->subtotal ?? 0) }}"
                                                    readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Discount (%)</strong></td>
                                            <td>
                                                <input type="text" name="discount" id="discount"
                                                    class="form-control discount-input"
                                                    onkeypress="return isNumberKey(event)"
                                                    value="{{ old('discount', $purchase_order->discount ?? 0) }}">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Discount Amount</strong></td>
                                            <td>
                                                <input type="text" name="discount_amount" id="discount_amount"
                                                    class="form-control"
                                                    value="{{ old('discount_amount', $purchase_order->discount_amount ?? 0) }}"
                                                    readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Tax (%)</strong></td>
                                            <td>
                                                <input type="text" name="tax" id="tax"
                                                    class="form-control tax-input" onkeypress="return isNumberKey(event)"
                                                    value="{{ old('tax', $purchase_order->tax ?? 0) }}">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Tax Amount</strong></td>
                                            <td>
                                                <input type="text" name="tax_amount" id="tax_amount"
                                                    class="form-control"
                                                    value="{{ old('tax_amount', $purchase_order->tax_amount ?? 0) }}"
                                                    readonly>
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Shipping Charge</strong></td>
                                            <td>
                                                <input type="text" name="shipping_charge"
                                                    onkeypress="return isNumberKey(event)" id="shipping_charge"
                                                    class="form-control shipping-input"
                                                    value="{{ old('shipping_charge', $purchase_order->shipping_charge ?? 0) }}">
                                            </td>
                                            <td></td>
                                        </tr>
                                        <tr>
                                            <td colspan="6" class="text-end"><strong>Grand Total</strong></td>
                                            <td>
                                                <input type="text" name="total" id="grand_total"
                                                    class="form-control"
                                                    value="{{ old('total', $purchase_order->total ?? 0) }}" readonly>
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
                        <button type="submit" class="btn btn-primary px-4">Save Purchase Order</button>
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
        var productIndex = {{ isset($purchase_order) ? $purchase_order->purchaseOrderDetails->count() : 0 }};

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
                row.find('.price-input').val(0);
                row.find('.quantity-input').val(1);
                row.find('.conversion-factor').val(1);

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
                row.find('.price-input').val(price);
                row.find('.conversion-factor').val(1);

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
                                        data-conversion-factor="${conversion.conversion_factor}">
                                        ${conversion.from_unit?.name || 'N/A'} to ${conversion.to_unit?.name || 'N/A'} 
                                        (${conversion.conversion_factor})
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
                row.find('.conversion-factor').val(conversionFactor);

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
                    addProductRow();
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
            const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
            const price = parseFloat(row.find('.price-input').val()) || 0;
            const conversionFactor = parseFloat(row.find('.conversion-factor').val()) || 1;

            // Apply conversion factor to quantity
            const convertedQuantity = quantity * conversionFactor;
            const total = convertedQuantity * price;

            // Update row total with 2 decimal places
            row.find('.row-total').val(total.toFixed(2));

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
            let rowCount = 0;

            $('#productRows tr').each(function() {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const price = parseFloat($(this).find('.price-input').val()) || 0;
                const conversionFactor = parseFloat($(this).find('.conversion-factor').val()) || 1;
                const convertedQuantity = quantity * conversionFactor;
                const total = convertedQuantity * price;
                subtotal += total;
                rowCount++;
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

            $('#subtotal').val(subtotal.toFixed(2));
            $('#discount_amount').val(discountAmount.toFixed(2));
            $('#tax_amount').val(taxAmount.toFixed(2));
            $('#grand_total').val(grandTotal.toFixed(2));

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
    </script>
@endsection
