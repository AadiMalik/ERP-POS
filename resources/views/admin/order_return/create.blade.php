@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($order_return) ? 'Update' : 'New' }} Order Return</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($order_return) ? 'Update' : 'Create' }} Order Return</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/order-return') }}" method="POST" id="orderReturnForm">
                    @csrf
                    <input type="hidden" name="order_return_id" value="{{ $order_return->order_return_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $order_return->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>
                                Order<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="order_id" id="order_id"
                                {{ isset($order_return) ? 'disabled' : '' }}>
                                <option value="">--Select Order--</option>
                                @foreach ($orders as $item)
                                    <option value="{{ $item->order_id }}"
                                        {{ old('order_id', $order_return->order_id ?? ($preselected_order_id ?? '')) == $item->order_id ? 'selected' : '' }}>
                                        {{ $item->daily_order_id }} - {{ $item->user->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($order_return))
                                <input type="hidden" name="order_id" value="{{ $order_return->order_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Customer</label>
                            <input type="text" class="form-control" id="customer_name" readonly
                                value="{{ $order_return->customer->name ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Warehouse</label>
                            <input type="text" class="form-control" id="warehouse_name" readonly
                                value="{{ $order_return->warehouse->name ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Return Number</label>
                            <input type="text" class="form-control" name="order_return_no" readonly
                                value="{{ $order_return->order_return_no ?? ($order_return_no ?? 'Auto Generated') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Return Date</label>
                            <input type="text" class="form-control datepicker" name="order_return_date"
                                value="{{ old('order_return_date', isset($order_return) ? localDate($order_return->order_return_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Refund Method</label>
                            <select class="form-control select2" name="refund_payment_method_id" id="refund_payment_method_id">
                                <option value="">-- Credit Customer's Account --</option>
                                @foreach ($payment_methods as $item)
                                    @continue($item->type === 'credit')
                                    <option value="{{ $item->payment_method_id }}"
                                        {{ old('refund_payment_method_id', $order_return->refund_payment_method_id ?? '') == $item->payment_method_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Leave blank to issue this return as a credit note against the customer's account instead of a cash/bank refund.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Reason</label>
                            <input type="text" class="form-control" name="reason"
                                value="{{ old('reason', $order_return->reason ?? '') }}">
                        </div>
                        <div class="col-md-12">
                            <label>Description</label>
                            <textarea class="form-control" rows="3" name="description">{{ old('description', $order_return->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <hr>
                    {{-- ================= PRODUCT TABLE ================= --}}
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                Products
                            </h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="productTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px;">Product</th>
                                        <th style="min-width:150px;">Variation</th>
                                        <th style="min-width:90px;">Unit</th>
                                        <th style="min-width:110px;">Ordered Qty</th>
                                        <th style="min-width:120px;">Already Returned</th>
                                        <th style="min-width:110px;">Returnable</th>
                                        <th style="min-width:130px;">Return Qty</th>
                                        <th style="min-width:120px;">Unit Price</th>
                                        <th style="min-width:90px;">Discount %</th>
                                        <th style="min-width:90px;">Tax %</th>
                                        <th style="min-width:130px">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    <tr id="emptyRow">
                                        <td colspan="11" class="text-center text-muted">
                                            Select an Order
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <br>
                    {{-- ================= FOOTER TOTALS ================= --}}
                    <div class="row">
                        <div class="offset-md-6 col-md-6">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Subtotal</th>
                                    <td>
                                        <input class="form-control" id="subtotal" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Discount</th>
                                    <td>
                                        <input class="form-control" id="discount_amount" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tax</th>
                                    <td>
                                        <input class="form-control" id="tax_amount" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total (Refund Amount)</th>
                                    <td>
                                        <input class="form-control fw-bold" id="total" name="total" readonly>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($order_return) ? 'Update Order Return' : 'Save Order Return' }}
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
        var isEditMode = {{ isset($order_return) ? 'true' : 'false' }};
        var editOrderReturnData = @json($order_return_details ?? null);

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            if (isEditMode) {
                loadOrderReturnForEdit();
            } else {
                let orderId = $('#order_id').val();
                if (orderId) {
                    loadSourceLines(orderId);
                }
            }
        });

        // ======================================================
        // SOURCE CHANGE
        // ======================================================

        $(document).on('change', '#order_id', function() {
            let order_id = $(this).val();

            if (!order_id) {
                resetProductRows();
                return;
            }

            loadSourceLines(order_id);
        });

        function resetProductRows() {
            $('#customer_name').val('');
            $('#warehouse_name').val('');
            $('#productRows').html(`
                <tr id="emptyRow">
                    <td colspan="11" class="text-center text-muted">
                        Select an Order
                    </td>
                </tr>
            `);
            calculateGrandTotal();
        }

        // ======================================================
        // LOAD RETURNABLE LINES (create mode)
        // ======================================================

        function loadSourceLines(order_id) {
            $.ajax({
                url: url_local + '/admin/order-return/source-lines/' + order_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#productRows').html(`
                        <tr>
                            <td colspan="11" class="text-center">
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
                    bindSourceHeader(response.Data.header);
                    bindProductRows(response.Data.lines);
                },
                error: function(error) {
                    errorMessage(error.responseJSON?.Message || 'Unable to load returnable lines.');
                }
            });
        }

        function bindSourceHeader(header) {
            if (!header) return;
            $('#customer_name').val(header.customer_name ?? '');
            $('#warehouse_name').val(header.warehouse_name ?? '');
        }

        function bindProductRows(lines) {
            $('#productRows').html('');

            if (!lines || !lines.length) {
                $('#productRows').html(`
                    <tr id="emptyRow">
                        <td colspan="11" class="text-center text-muted">
                            Nothing remaining to return for this order.
                        </td>
                    </tr>
                `);
                calculateGrandTotal();
                return;
            }

            $.each(lines, function(_, line) {
                addProductRow(line);
            });

            calculateGrandTotal();
        }

        // ======================================================
        // ROW TEMPLATE
        // ======================================================

        function addProductRow(line, return_quantity) {
            return_quantity = return_quantity ?? 0;

            let row = $(`
                <tr class="product-row">
                    <td>
                        <input type="hidden" name="products[][order_detail_id]" value="${line.order_detail_id}">
                        <input type="hidden" name="products[][reason]" value="">
                        ${line.product_name}
                    </td>
                    <td>${line.product_variation_name}</td>
                    <td>${line.unit_name}</td>
                    <td class="ordered-qty">${decimal(line.ordered_quantity)}</td>
                    <td class="already-returned-qty">${decimal(line.already_returned_quantity)}</td>
                    <td class="returnable-qty">${decimal(line.returnable_quantity)}</td>
                    <td>
                        <input type="text" class="form-control return-qty"
                            name="products[${$('#productRows tr.product-row').length}][return_quantity]"
                            value="${decimal(return_quantity)}"
                            data-returnable="${line.returnable_quantity}">
                    </td>
                    <td class="unit-price">${decimal(line.unit_price)}</td>
                    <td class="discount-percent">${decimal(line.discount)}</td>
                    <td class="tax-percent">${decimal(line.tax)}</td>
                    <td class="row-total">${decimal(0)}</td>
                </tr>
            `);

            row.data('unit_price', line.unit_price);
            row.data('conversion_factor', line.conversion_factor || 1);
            row.data('discount_percent', line.discount || 0);
            row.data('tax_percent', line.tax || 0);
            row.find('input[name*="[order_detail_id]"]').attr('name',
                `products[${$('#productRows tr.product-row').length}][order_detail_id]`);

            $('#productRows').append(row);
            reindexRows();
            calculateRow($('#productRows tr.product-row').last());
        }

        function reindexRows() {
            $('#productRows tr.product-row').each(function(index) {
                $(this).find('input[name*="[order_detail_id]"]').attr('name',
                    `products[${index}][order_detail_id]`);
                $(this).find('input.return-qty').attr('name', `products[${index}][return_quantity]`);
                $(this).find('input[name*="[reason]"]').attr('name', `products[${index}][reason]`);
            });
        }

        // ======================================================
        // RETURN QTY CHANGE
        // ======================================================

        function calculateRow(row) {
            let returnable = decimal(row.find('.return-qty').data('returnable'));
            let qty = decimal(row.find('.return-qty').val());

            if (qty > returnable) {
                qty = returnable;
                row.find('.return-qty').val(decimal(qty));
            }
            if (qty < 0) {
                qty = 0;
                row.find('.return-qty').val(decimal(qty));
            }

            let unitPrice = decimal(row.data('unit_price'));
            let conversionFactor = decimal(row.data('conversion_factor')) || 1;
            let discountPercent = decimal(row.data('discount_percent'));
            let taxPercent = decimal(row.data('tax_percent'));

            let baseQty = qty * conversionFactor;
            let subtotal = baseQty * unitPrice;
            let discountAmount = round(subtotal * discountPercent / 100, 3);
            let taxable = subtotal - discountAmount;
            let taxAmount = round(taxable * taxPercent / 100, 3);
            let total = taxable + taxAmount;

            row.find('.row-total').html(decimal(total));
            row.data('subtotal', subtotal);
            row.data('discount_amount', discountAmount);
            row.data('tax_amount', taxAmount);
            row.data('total', total);
        }

        function round(value, precision) {
            let factor = Math.pow(10, precision);
            return Math.round((value + Number.EPSILON) * factor) / factor;
        }

        $(document).on('keyup change', '.return-qty', function() {
            let row = $(this).closest('tr');
            calculateRow(row);
            calculateGrandTotal();
        });

        $(document).on('blur', '.return-qty', function() {
            $(this).val(decimal($(this).val()));
        });

        // ======================================================
        // GRAND TOTAL
        // ======================================================

        function calculateGrandTotal() {
            let subtotal = 0;
            let discount_amount = 0;
            let tax_amount = 0;
            let total = 0;

            $('#productRows tr.product-row').each(function() {
                subtotal += decimal($(this).data('subtotal'));
                discount_amount += decimal($(this).data('discount_amount'));
                tax_amount += decimal($(this).data('tax_amount'));
                total += decimal($(this).data('total'));
            });

            $('#subtotal').val(decimal(subtotal));
            $('#discount_amount').val(decimal(discount_amount));
            $('#tax_amount').val(decimal(tax_amount));
            $('#total').val(decimal(total));
        }

        // ======================================================
        // EDIT MODE
        // ======================================================

        function loadOrderReturnForEdit() {
            if (!editOrderReturnData || !editOrderReturnData.details || !editOrderReturnData.details.length) {
                return;
            }

            bindSourceHeader({
                customer_name: $('#customer_name').val(),
                warehouse_name: $('#warehouse_name').val()
            });

            $('#productRows').html('');

            $.each(editOrderReturnData.details, function(_, item) {
                addProductRow({
                    order_detail_id: item.order_detail_id,
                    product_name: item.product_name,
                    product_variation_name: item.product_variation_name,
                    ordered_quantity: item.ordered_quantity,
                    already_returned_quantity: item.already_returned_quantity,
                    returnable_quantity: (decimal(item.ordered_quantity) - decimal(item
                        .already_returned_quantity)) + decimal(item.return_quantity),
                    unit_name: item.unit_name,
                    unit_price: item.unit_price,
                    conversion_factor: item.conversion_factor,
                    discount: item.discount,
                    tax: item.tax
                }, item.return_quantity);
            });

            calculateGrandTotal();
        }

        // ======================================================
        // FORM SUBMIT
        // ======================================================

        $('#orderReturnForm').on('submit', function(e) {
            if ($('#productRows tr.product-row').length == 0) {
                e.preventDefault();
                errorMessage('Please select an order with returnable products.');
                return false;
            }

            let hasQuantity = false;
            $('#productRows .return-qty').each(function() {
                if (decimal($(this).val()) > 0) {
                    hasQuantity = true;
                }
            });

            if (!hasQuantity) {
                e.preventDefault();
                errorMessage('Please enter a return quantity for at least one product.');
                return false;
            }
        });
    </script>
@endsection
