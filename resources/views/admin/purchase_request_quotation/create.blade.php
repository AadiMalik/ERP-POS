@php
use App\Enums\RoleNames;
use Carbon\Carbon;
@endphp
@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ isset($purchase_request_quotation) ? 'Update' : 'New' }} Purchase Request Quotation</h4>
    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($purchase_request_quotation) ? 'Update' : 'Create' }} Purchase Request Quotation</h5>
        </div>
        <form action="{{ url('admin/purchase-request-quotation') }}" method="POST">
            @csrf
            <input type="hidden" name="purchase_request_quotation_id"
                value="{{ isset($purchase_request_quotation) ? $purchase_request_quotation->purchase_request_quotation_id : '' }}">
            <div class="card-body">
                <!-- Header Information -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3">
                        <label class="fw-semibold">Quotation No</label>
                        <input type="text" class="form-control" name="purchase_request_quotation_no"
                            value="{{ $purchase_request_quotation->purchase_request_quotation_no ?? ($purchase_request_quotation_no ?? 'Auto Generated') }}"
                            readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Purchase Request<span class="text-danger">*</span></label>
                        <select class="form-select" name="purchase_request_id" id="purchase_request_id">
                            <option value="">-- Select Purchase Request --</option>
                            @foreach ($purchase_requests as $item)
                            <option value="{{ $item->purchase_request_id }}"
                                {{ old('purchase_request_id', $purchase_request_quotation->purchase_request_id ?? '') == $item->purchase_request_id ? 'selected' : '' }}>
                                {{ $item->purchase_request_no }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="fw-semibold mb-0">Supplier<span class="text-danger">*</span></label>
                            @include('admin.partials.quick-add-btn', ['permission' => 'supplier.create', 'modal' => 'quickAddSupplierModal', 'label' => 'Supplier'])
                        </div>
                        <select class="form-select" name="supplier_id" id="supplier_id" required>
                            <option value="">-- Select Supplier --</option>
                            @if (RoleNames::SUPERADMIN != getRoleName())
                            @foreach ($suppliers as $item)
                            <option value="{{ $item->supplier_id }}"
                                {{ isset($purchase_request_quotation) ? ($purchase_request_quotation->supplier_id == $item->supplier_id ? 'selected' : '') : '' }}>
                                {{ $item->name }}
                            </option>
                            @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Supplier Reference No</label>
                        <input type="text" class="form-control" name="vendor_reference_no"
                            value="{{ $purchase_request_quotation->vendor_reference_no ?? '' }}">
                    </div>

                    <div class="col-md-3">
                        <label class="fw-semibold">Received Date</label>
                        <input type="text" class="form-control datepicker" name="received_date"
                            value="{{ old('received_date', isset($purchase_request_quotation) ? localDate($purchase_request_quotation->received_date) : localDate(date('Y-m-d'))) }}">
                    </div>
                    <div class="col-md-3" style="margin-top: 3.5rem !important;">
                        <input type="checkbox" class="form-check-input" name="send_email" id="send_email" value="">
                        <label class="form-check-label" for="send_email">Send Email</label>
                    </div>
                    <div class="col-md-3" style="margin-top: 3.5rem !important;">
                        <input type="checkbox" class="form-check-input" name="send_whatsapp" id="send_whatsapp" value="">
                        <label class="form-check-label" for="send_whatsapp">Send Whatsapp</label>
                    </div>
                    <div class="col-md-3" style="margin-top: 3.5rem !important;">
                        <input type="checkbox" class="form-check-input" name="send_sms" id="send_sms" value="">
                        <label class="form-check-label" for="send_sms">Send SMS</label>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Description</label>
                        <textarea class="form-control" name="description">{{ old('description', $purchase_request_quotation->description ?? '') }}</textarea>
                    </div>
                </div>

                <!-- Products Section -->
                <div class="col-md-12 mb-4">
                    <h5 class="mb-0">Products</h5>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="productTable">
                            <thead>
                                <tr>
                                    <th style="min-width:220px;">Product</th>
                                    <th style="min-width:180px;">Variation</th>
                                    <th style="min-width:90px;">Unit</th>
                                    <th style="min-width:120px;">Requested Qty</th>
                                    <th style="min-width:120px;">Quoted Qty</th>
                                    <th style="min-width:120px;">Unit Price</th>
                                    <th style="min-width:130px;">Subtotal</th>
                                    <th style="min-width:90px;">Disc %</th>
                                    <th style="min-width:130px;">Disc Amount</th>
                                    <th style="min-width:90px;">Tax %</th>
                                    <th style="min-width:130px;">Tax Amount</th>
                                    <th style="min-width:130px;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="productRows">
                                @if (isset($purchase_request_quotation) && $purchase_request_quotation->purchaseRequestQuotationDetails->count() > 0)
                                @foreach ($purchase_request_quotation->purchaseRequestQuotationDetails as $detail)
                                <tr>

                                    <td>
                                        <input type="hidden"
                                            name="products[{{ $loop->index }}][product_id]"
                                            value="{{ $detail->product_id }}">

                                        {{ $detail->product->name ?? '' }}
                                    </td>

                                    <td>
                                        <input type="hidden"
                                            name="products[{{ $loop->index }}][product_variation_id]"
                                            value="{{ $detail->product_variation_id }}">

                                        {{ $detail->productVariation->name ?? '' }}
                                    </td>

                                    <td>
                                        <input type="hidden"
                                            name="products[{{ $loop->index }}][unit_id]"
                                            value="{{ $detail->unit_id }}">

                                        {{ $detail->unit->name ?? '' }}
                                    </td>

                                    <td>
                                        <input type="text"
                                            class="form-control"
                                            readonly
                                            value="{{ decimal($detail->requested_quantity) }}">

                                        <input type="hidden"
                                            name="products[{{ $loop->index }}][requested_quantity]"
                                            value="{{ decimal($detail->requested_quantity) }}">
                                    </td>

                                    <td>
                                        <input type="text"
                                            name="products[{{ $loop->index }}][quoted_quantity]"
                                            class="form-control quoted-qty"
                                            value="{{ decimal($detail->quoted_quantity) }}"
                                            max="{{ $detail->requested_quantity }}"
                                            onkeypress="return isNumberKey(event)">
                                    </td>

                                    <td>
                                        <input type="text"
                                            name="products[{{ $loop->index }}][unit_price]"
                                            class="form-control unit-price"
                                            value="{{ decimal($detail->unit_price) }}"
                                            onkeypress="return isNumberKey(event)">
                                    </td>

                                    <td>
                                        <input type="text"
                                            readonly
                                            class="form-control row-subtotal"
                                            name="products[{{ $loop->index }}][subtotal]"
                                            value="{{ decimal($detail->subtotal) }}">
                                    </td>

                                    <td>
                                        <input type="text"
                                            name="products[{{ $loop->index }}][discount]"
                                            class="form-control row-discount"
                                            value="{{ decimal($detail->discount) }}"
                                            onkeypress="return isNumberKey(event)">
                                    </td>

                                    <td>
                                        <input type="text"
                                            readonly
                                            class="form-control row-discount-amount"
                                            name="products[{{ $loop->index }}][discount_amount]"
                                            value="{{ decimal($detail->discount_amount) }}">
                                    </td>

                                    <td>
                                        <input type="text"
                                            name="products[{{ $loop->index }}][tax]"
                                            class="form-control row-tax"
                                            value="{{ decimal($detail->tax) }}"
                                            onkeypress="return isNumberKey(event)">
                                    </td>

                                    <td>
                                        <input type="text"
                                            readonly
                                            class="form-control row-tax-amount"
                                            name="products[{{ $loop->index }}][tax_amount]"
                                            value="{{ decimal($detail->tax_amount) }}">
                                    </td>

                                    <td>
                                        <input type="text"
                                            readonly
                                            class="form-control row-total"
                                            name="products[{{ $loop->index }}][total]"
                                            value="{{ decimal($detail->total) }}">
                                    </td>

                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                            <tfoot>

                                <tr>
                                    <td colspan="10" class="text-end">
                                        <strong>Subtotal</strong>
                                    </td>

                                    <td colspan="2">
                                        <input type="text"
                                            id="subtotal"
                                            name="subtotal"
                                            readonly
                                            class="form-control"
                                            value="{{ old('subtotal', decimal($purchase_request_quotation->subtotal ?? 0)) }}">
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="10" class="text-end">
                                        <strong>Discount Amount</strong>
                                    </td>

                                    <td colspan="2">
                                        <input type="text"
                                            id="discount_amount"
                                            name="discount_amount"
                                            readonly
                                            class="form-control"
                                            value="{{ old('discount_amount', decimal($purchase_request_quotation->discount_amount ?? 0)) }}">
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="10" class="text-end">
                                        <strong>Tax Amount</strong>
                                    </td>

                                    <td colspan="2">
                                        <input type="text"
                                            id="tax_amount"
                                            name="tax_amount"
                                            readonly
                                            class="form-control"
                                            value="{{ old('tax_amount', decimal($purchase_request_quotation->tax_amount ?? 0)) }}">
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="10" class="text-end">
                                        <strong>Other Charges</strong>
                                    </td>

                                    <td colspan="2">
                                        <input type="text"
                                            id="other_charge"
                                            name="other_charge"
                                            class="form-control other-charge"
                                            value="{{ old('other_charge', decimal($purchase_request_quotation->other_charge ?? 0)) }}"
                                            onkeypress="return isNumberKey(event)">
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="10" class="text-end">
                                        <strong>Total</strong>
                                    </td>

                                    <td colspan="2">
                                        <input type="text"
                                            id="total"
                                            name="total"
                                            readonly
                                            class="form-control"
                                            value="{{ old('total', decimal($purchase_request_quotation->total ?? 0)) }}">
                                    </td>
                                </tr>

                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>

            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

@include('admin.supplier.model.quick-create', ['business' => $business ?? []])
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
            $('#business_id, #supplier_id, #purchase_request_id').select2();
        }

        initQuickAdd({
            modalId: '#quickAddSupplierModal',
            formId: '#quickAddSupplierForm',
            url: url_local + '/admin/supplier',
            valueField: 'supplier_id',
            labelField: 'name',
            targetSelectIds: ['supplier_id'],
        });

        // Set initial product index based on existing rows
        productIndex = $('#productRows tr').length || 0;


        // ============================================
        // 3. PURCHASE REQUEST SELECTION
        // ============================================
        $('#purchase_request_id').on('change', function() {

            let id = $(this).val();

            $('#productRows').html('');

            if (id == '') {
                calculateGrandTotal();
                return;
            }

            $.ajax({
                url: url_local + '/admin/purchase-request/details/' + id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {

                    if (!response.Success) {
                        errorMessage(response.Message);
                        return;
                    }

                    loadPurchaseRequest(response.Data);
                },
                error: function() {
                    errorMessage('Unable to load purchase request.');
                }
            });

        });

        // ============================================
        // 4. LOAD PURCHASE REQUEST DATA
        // ============================================
        function loadPurchaseRequest(data) {

            $('#productRows').html('');

            if (data.header.supplier_id) {
                $('#supplier_id').val(data.header.supplier_id).trigger('change');
            }

            $('textarea[name=description]').val(data.header.description ?? '');

            productIndex = 0;

            $.each(data.details, function(i, item) {

                appendRow(item);

            });

            calculateGrandTotal();

        }

        // ============================================
        // 5. ADD PURCHASE REQUEST ROW
        // ============================================
        function appendRow(item) {

            let html = `

<tr>

<td>

${item.product_name}

<input type="hidden"
name="products[${productIndex}][product_id]"
value="${item.product_id}">

<input type="hidden"
name="products[${productIndex}][purchase_request_detail_id]"
value="${item.purchase_request_detail_id}">

</td>

<td>

${item.product_variation_name}

<input type="hidden"
name="products[${productIndex}][product_variation_id]"
value="${item.product_variation_id}">

</td>

<td>

${item.unit_name}

<input type="hidden"
name="products[${productIndex}][unit_id]"
value="${item.unit_id}">

</td>

<td>

<input
class="form-control"
readonly 
value="${decimal(item.requested_quantity)}">

<input
type="hidden"
name="products[${productIndex}][requested_quantity]"
value="${item.requested_quantity}">

</td>

<td>

<input
type="text"
class="form-control quoted-qty"
name="products[${productIndex}][quoted_quantity]"
value="${decimal(item.requested_quantity)}"
max="${item.requested_quantity}" onkeypress="return isNumberKey(event)">

</td>

<td>

<input
type="text"
class="form-control unit-price"
name="products[${productIndex}][unit_price]"
value="" onkeypress="return isNumberKey(event)">

</td>

<td>

<input
type="text"
readonly
class="form-control row-subtotal"
name="products[${productIndex}][subtotal]"
value="0">

</td>

<td>

<input
type="text"
class="form-control row-discount"
name="products[${productIndex}][discount]"
value="0" onkeypress="return isNumberKey(event)">

</td>

<td>

<input
type="text"
readonly
class="form-control row-discount-amount"
name="products[${productIndex}][discount_amount]"
value="0">

</td>

<td>

<input
type="text"
class="form-control row-tax"
name="products[${productIndex}][tax]"
value="0" onkeypress="return isNumberKey(event)">

</td>

<td>

<input
type="text"
readonly
class="form-control row-tax-amount"
name="products[${productIndex}][tax_amount]"
value="0">

</td>

<td>

<input
type="text"
readonly
class="form-control row-total"
name="products[${productIndex}][total]"
value="0">

</td>

</tr>

`;

            $('#productRows').append(html);

            productIndex++;

        }

        // ============================================
        // 9. CALCULATE ROW TOTAL
        // ============================================
        function calculateRow(row) {

            let qty = parseFloat(row.find('.quoted-qty').val()) || 0;

            let req = parseFloat(row.find('input[name*="[requested_quantity]"]').val()) || 0;

            if (qty > req) {

                qty = req;

                row.find('.quoted-qty').val(req);

            }

            let price = parseFloat(row.find('.unit-price').val()) || 0;

            let subtotal = qty * price;

            let discount = parseFloat(row.find('.row-discount').val()) || 0;

            let discountAmount = subtotal * discount / 100;

            let afterDiscount = subtotal - discountAmount;

            let tax = parseFloat(row.find('.row-tax').val()) || 0;

            let taxAmount = afterDiscount * tax / 100;

            let total = afterDiscount + taxAmount;

            row.find('.row-subtotal').val(decimal(subtotal));

            row.find('.row-discount-amount').val(decimal(discountAmount));

            row.find('.row-tax-amount').val(decimal(taxAmount));

            row.find('.row-total').val(decimal(total));

        }

        // ============================================
        // 10. CALCULATE GRAND TOTALS
        // ============================================
        function calculateGrandTotal() {

            let subtotal = 0;

            let discountAmount = 0;

            let taxAmount = 0;

            let total = 0;

            $('#productRows tr').each(function() {

                subtotal += parseFloat($(this).find('.row-subtotal').val()) || 0;

                discountAmount += parseFloat($(this).find('.row-discount-amount').val()) || 0;

                taxAmount += parseFloat($(this).find('.row-tax-amount').val()) || 0;

                total += parseFloat($(this).find('.row-total').val()) || 0;

            });

            let other = parseFloat($('#other_charge').val()) || 0;

            total += other;

            $('#subtotal').val(decimal(subtotal));

            $('#discount_amount').val(decimal(discountAmount));

            $('#tax_amount').val(decimal(taxAmount));

            $('#total').val(decimal(total));

        }

        // ============================================
        // 11. ROW DISCOUNT/TAX CHANGES
        // ============================================
        $(document).on('keyup change',

            '.quoted-qty,.unit-price,.row-discount,.row-tax',

            function() {

                let row = $(this).closest('tr');

                calculateRow(row);

                calculateGrandTotal();

            });

        $(document).on('keyup change', '#other_charge', function() {

            calculateGrandTotal();

        });

        // ============================================
        // 15. QUANTITY INPUT VALIDATION
        // ============================================
        $(document).on('input change', '.quantity-input', function() {
            const row = $(this).closest('tr');
            const requestedQty = parseFloat($(this).data('requested-qty')) || 0;
            const currentQty = parseFloat($(this).val()) || 0;

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

        });

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


        setTimeout(function() {
            $('#productRows tr').each(function() {

                calculateRow($(this));

            });

            calculateGrandTotal();
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
    $(document).on('submit', 'form[action*="/admin/purchase-request-quotation"]', function(e) {

        let isValid = true;
        let errorMessages = [];

        $('.is-invalid').removeClass('is-invalid');

        // Supplier
        if (!$('#supplier_id').val()) {
            isValid = false;
            $('#supplier_id').addClass('is-invalid');
            errorMessages.push('Please select supplier.');
        }

        // Purchase Request
        if (!$('#purchase_request_id').val()) {
            isValid = false;
            $('#purchase_request_id').addClass('is-invalid');
            errorMessages.push('Please select purchase request.');
        }

        // Products
        if ($('#productRows tr').length == 0) {
            isValid = false;
            errorMessages.push('No products found.');
        }

        $('#productRows tr').each(function(index) {

            let requestedQty = parseFloat($(this).find('input[name*="[requested_quantity]"]').val()) || 0;
            let quotedQty = parseFloat($(this).find('.quoted-qty').val()) || 0;
            let unitPrice = $(this).find('.unit-price').val();

            if (quotedQty < 0 || quotedQty > requestedQty) {

                isValid = false;

                $(this).find('.quoted-qty').addClass('is-invalid');

                errorMessages.push(
                    'Row ' + (index + 1) + ': Quoted quantity must be between 0 and ' + requestedQty + '.'
                );
            }

            if (unitPrice !== '' && parseFloat(unitPrice) < 0) {

                isValid = false;

                $(this).find('.unit-price').addClass('is-invalid');

                errorMessages.push(
                    'Row ' + (index + 1) + ': Unit price cannot be negative.'
                );
            }

        });

        if (!isValid) {

            e.preventDefault();

            errorMessage(errorMessages.join('<br>'));

            $('.is-invalid:first').focus();

            return false;
        }

        return true;

    });

    $(document).on('input change', '.is-invalid', function() {
        $(this).removeClass('is-invalid');
    });
</script>
@endsection