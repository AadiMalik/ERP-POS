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
                                        <th style="min-width:160px;">Serial #</th>
                                        <th style="min-width:120px;">Unit Price</th>
                                        <th style="min-width:90px;">Discount %</th>
                                        <th style="min-width:90px;">Tax %</th>
                                        <th style="min-width:130px">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    <tr id="emptyRow">
                                        <td colspan="12" class="text-center text-muted">
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
                    <td colspan="12" class="text-center text-muted">
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
                            <td colspan="12" class="text-center">
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
                        <td colspan="12" class="text-center text-muted">
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
                    <td class="serial-cell" data-track-serial="${line.track_serial_number ? 1 : 0}" data-order-detail-id="${line.order_detail_id}">
                        <button type="button" class="btn btn-sm btn-outline-primary serial-entry-btn" style="${line.track_serial_number ? '' : 'display:none;'}">
                            <i class="fa fa-list-check"></i> <span class="serial-count-label">Select Serials (0/0)</span>
                        </button>
                        <span class="serial-na text-muted" style="${line.track_serial_number ? 'display:none;' : ''}">N/A</span>
                        <div class="serial-hidden-inputs" style="display:none;"></div>
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

            if (line.track_serial_number && line.serial_numbers && line.serial_numbers.length) {
                let addedRow = $('#productRows tr.product-row').last();
                addedRow.find('.serial-hidden-inputs').html(
                    line.serial_numbers.map(sn => `<input type="hidden" class="serial-hidden-input" value="${sn}">`).join('')
                );
                reindexRows();
                refreshSerialButtonLabel(addedRow);
            }
        }

        function reindexRows() {
            $('#productRows tr.product-row').each(function(index) {
                $(this).find('input[name*="[order_detail_id]"]').attr('name',
                    `products[${index}][order_detail_id]`);
                $(this).find('input.return-qty').attr('name', `products[${index}][return_quantity]`);
                $(this).find('input[name*="[reason]"]').attr('name', `products[${index}][reason]`);
                $(this).find('input.serial-hidden-input').attr('name', `products[${index}][serial_numbers][]`);
            });
        }

        function refreshSerialButtonLabel(row) {
            const entered = row.find('.serial-hidden-input').length;
            const expected = decimal(row.find('.return-qty').val()) || 0;
            row.find('.serial-count-label').text(`Select Serials (${entered}/${expected})`);
            row.find('.serial-entry-btn').toggleClass('btn-outline-primary', entered == expected)
                .toggleClass('btn-outline-danger', entered != expected);
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
            refreshSerialButtonLabel(row);
        });

        // ======================================================
        // SERIAL NUMBER PICKER MODAL (select from what's currently sold
        // under this order line, rather than freely typed)
        // ======================================================
        var orSerialModal = null;
        var currentOrSerialRow = null;

        $(document).on('click', '.serial-entry-btn', function() {
            currentOrSerialRow = $(this).closest('tr');
            let cell = currentOrSerialRow.find('.serial-cell');
            let orderDetailId = cell.data('order-detail-id');
            let expected = decimal(currentOrSerialRow.find('.return-qty').val()) || 0;
            let alreadyChecked = currentOrSerialRow.find('.serial-hidden-input').map(function() {
                return $(this).val();
            }).get();

            $('#orSerialModalHint').text(`Select exactly ${expected} serial number(s) to return.`);
            $('#orSerialModalList').html('<div class="text-muted">Loading...</div>');
            orSerialModal = orSerialModal || new bootstrap.Modal(document.getElementById('orSerialModal'));
            orSerialModal.show();

            $.ajax({
                url: url_local + '/admin/order-return/sold-serials/' + orderDetailId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response.Success || !response.Data.length) {
                        $('#orSerialModalList').html('<div class="text-muted">No sold serial numbers found for this line.</div>');
                        return;
                    }
                    let html = '';
                    $.each(response.Data, function(_, s) {
                        let checked = alreadyChecked.includes(s.serial_no) ? 'checked' : '';
                        html += `
                            <div class="form-check">
                                <input class="form-check-input or-serial-checkbox" type="checkbox" value="${s.serial_no}" id="orSerial_${s.product_variation_serial_number_id}" ${checked}>
                                <label class="form-check-label" for="orSerial_${s.product_variation_serial_number_id}">${s.serial_no}</label>
                            </div>
                        `;
                    });
                    $('#orSerialModalList').html(html);
                },
                error: function() {
                    $('#orSerialModalList').html('<div class="text-danger">Unable to load serial numbers.</div>');
                }
            });
        });

        $('#orSerialModalSaveBtn').on('click', function() {
            if (!currentOrSerialRow) return;
            let selected = $('.or-serial-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            let expected = parseFloat(currentOrSerialRow.find('.return-qty').val()) || 0;
            if (selected.length !== expected) {
                errorMessage(`Select exactly ${expected} serial number(s) (currently ${selected.length}).`);
                return;
            }

            currentOrSerialRow.find('.serial-hidden-inputs').html(
                selected.map(sn => `<input type="hidden" class="serial-hidden-input" value="${sn}">`).join('')
            );
            reindexRows();
            refreshSerialButtonLabel(currentOrSerialRow);
            bootstrap.Modal.getInstance(document.getElementById('orSerialModal')).hide();
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
                    tax: item.tax,
                    track_serial_number: item.track_serial_number,
                    serial_numbers: item.serial_numbers || []
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

            let serialMismatch = false;
            $('#productRows tr.product-row').each(function() {
                let row = $(this);
                if (row.find('.serial-cell').data('track-serial') != 1) {
                    return;
                }
                let qty = parseFloat(row.find('.return-qty').val()) || 0;
                let enteredCount = row.find('.serial-hidden-input').length;
                if (qty > 0 && enteredCount !== qty) {
                    errorMessage(`Select exactly ${qty} serial number(s) for "${row.find('td').eq(0).text().trim()}" (currently ${enteredCount}).`);
                    serialMismatch = true;
                    return false;
                }
            });

            if (serialMismatch) {
                e.preventDefault();
                return false;
            }
        });
    </script>

    {{-- ================= SERIAL NUMBER PICKER MODAL ================= --}}
    <div class="modal fade" id="orSerialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Serial Numbers to Return</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2" id="orSerialModalHint">Select the serial numbers to return.</p>
                    <div id="orSerialModalList" style="max-height:300px; overflow-y:auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="orSerialModalSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection
