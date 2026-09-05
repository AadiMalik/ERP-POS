@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($purchase_return) ? 'Update' : 'New' }} {{ __('purchase_returns.singular') }}</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($purchase_return) ? 'Update' : 'Create' }} {{ __('purchase_returns.singular') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/purchase-return') }}" method="POST" id="purchaseReturnForm">
                    @csrf
                    <input type="hidden" name="purchase_return_id" value="{{ $purchase_return->purchase_return_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.business') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">{{ __('common.select_business') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $purchase_return->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>{{ __('purchase_returns.return_type') }} <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="return_type" id="return_type"
                                {{ isset($purchase_return) ? 'disabled' : '' }}>
                                <option value="direct"
                                    {{ old('return_type', $purchase_return->return_type ?? '') == 'direct' ? 'selected' : '' }}>
                                    Direct Purchase</option>
                                <option value="grn"
                                    {{ old('return_type', $purchase_return->return_type ?? '') == 'grn' ? 'selected' : '' }}>
                                    GRN</option>
                            </select>
                            @if (isset($purchase_return))
                                <input type="hidden" name="return_type" value="{{ $purchase_return->return_type }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3 source-direct">
                            <label>
                                Purchase<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="purchase_id" id="purchase_id"
                                {{ isset($purchase_return) ? 'disabled' : '' }}>
                                <option value="">{{ __('purchase_returns.select_purchase') }}</option>
                                @foreach ($direct_purchases as $item)
                                    <option value="{{ $item->purchase_id }}"
                                        {{ old('purchase_id', $purchase_return->return_type === 'direct' ? ($purchase_return->purchase_id ?? '') : '') == $item->purchase_id ? 'selected' : '' }}>
                                        {{ $item->purchase_no }} - {{ $item->supplier->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($purchase_return) && $purchase_return->return_type === 'direct')
                                <input type="hidden" name="purchase_id" value="{{ $purchase_return->purchase_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3 source-grn" style="display:none;">
                            <label>
                                GRN<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="good_receipt_note_id" id="good_receipt_note_id"
                                {{ isset($purchase_return) ? 'disabled' : '' }}>
                                <option value="">{{ __('purchase_returns.select_grn') }}</option>
                                @foreach ($grns as $item)
                                    <option value="{{ $item->good_receipt_note_id }}"
                                        {{ old('good_receipt_note_id', $purchase_return->return_type === 'grn' ? ($purchase_return->good_receipt_note_id ?? '') : '') == $item->good_receipt_note_id ? 'selected' : '' }}>
                                        {{ $item->good_receipt_note_no }} - {{ $item->supplier->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($purchase_return) && $purchase_return->return_type === 'grn')
                                <input type="hidden" name="good_receipt_note_id" value="{{ $purchase_return->good_receipt_note_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('common.supplier') }}</label>
                            <input type="text" class="form-control" id="supplier_name" readonly
                                value="{{ $purchase_return->supplier->name ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('common.warehouse') }}</label>
                            <input type="text" class="form-control" id="warehouse_name" readonly
                                value="{{ $purchase_return->warehouse->name ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Return Number</label>
                            <input type="text" class="form-control" name="purchase_return_no" readonly
                                value="{{ $purchase_return->purchase_return_no ?? ($purchase_return_no ?? '{{ __('common.auto_generated') }}') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('purchase_returns.return_date') }}</label>
                            <input type="text" class="form-control datepicker" name="purchase_return_date"
                                value="{{ old('purchase_return_date', isset($purchase_return) ? localDate($purchase_return->purchase_return_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>{{ __('common.reason') }}</label>
                            <input type="text" class="form-control" name="reason"
                                value="{{ old('reason', $purchase_return->reason ?? '') }}">
                        </div>
                        <div class="col-md-12">
                            <label>{{ __('common.description') }}</label>
                            <textarea class="form-control" rows="3" name="description">{{ old('description', $purchase_return->description ?? '') }}</textarea>
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
                                        <th style="min-width:220px;">{{ __('common.product') }}</th>
                                        <th style="min-width:150px;">{{ __('common.variation') }}</th>
                                        <th style="min-width:150px;">Batch / Expiry</th>
                                        <th style="min-width:160px;">{{ __('common.serial_number') }}</th>
                                        <th style="min-width:90px;">{{ __('common.unit') }}</th>
                                        <th style="min-width:110px;">{{ __('common.received_qty') }}</th>
                                        <th style="min-width:120px;">{{ __('purchase_returns.already_returned') }}</th>
                                        <th style="min-width:110px;">{{ __('purchase_returns.returnable') }}</th>
                                        <th style="min-width:130px;">{{ __('purchase_returns.return_qty') }}</th>
                                        <th style="min-width:120px;">{{ __('common.unit_cost') }}</th>
                                        <th style="min-width:90px;">Discount %</th>
                                        <th style="min-width:90px;">{{ __('common.tax_percent') }}</th>
                                        <th style="min-width:130px">{{ __('common.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    <tr id="emptyRow">
                                        <td colspan="13" class="text-center text-muted">
                                            Select a Purchase or GRN
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
                                    <th>{{ __('common.subtotal') }}</th>
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
                            <button class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($purchase_return) ? 'Update {{ __('purchase_returns.singular') }}' : 'Save {{ __('purchase_returns.singular') }}' }}
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
        var isEditMode = {{ isset($purchase_return) ? 'true' : 'false' }};
        var editPurchaseReturnData = @json($purchase_return_details ?? null);

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            toggleSourceFields($('#return_type').val());

            if (isEditMode) {
                loadPurchaseReturnForEdit();
            } else {
                let sourceId = $('#return_type').val() === 'grn' ? $('#good_receipt_note_id').val() : $(
                    '#purchase_id').val();
                if (sourceId) {
                    loadSourceLines($('#return_type').val(), sourceId);
                }
            }
        });

        // ======================================================
        // RETURN TYPE TOGGLE
        // ======================================================

        function toggleSourceFields(return_type) {
            if (return_type === 'grn') {
                $('.source-direct').hide();
                $('.source-grn').show();
            } else {
                $('.source-grn').hide();
                $('.source-direct').show();
            }
        }

        $(document).on('change', '#return_type', function() {
            toggleSourceFields($(this).val());
            resetProductRows();
        });

        // ======================================================
        // SOURCE CHANGE
        // ======================================================

        $(document).on('change', '#purchase_id', function() {
            let purchase_id = $(this).val();

            if (!purchase_id) {
                resetProductRows();
                return;
            }

            loadSourceLines('direct', purchase_id);
        });

        $(document).on('change', '#good_receipt_note_id', function() {
            let good_receipt_note_id = $(this).val();

            if (!good_receipt_note_id) {
                resetProductRows();
                return;
            }

            loadSourceLines('grn', good_receipt_note_id);
        });

        function resetProductRows() {
            $('#supplier_name').val('');
            $('#warehouse_name').val('');
            $('#productRows').html(`
                <tr id="emptyRow">
                    <td colspan="13" class="text-center text-muted">
                        Select a Purchase or GRN
                    </td>
                </tr>
            `);
            calculateGrandTotal();
        }

        // ======================================================
        // LOAD RETURNABLE LINES (create mode)
        // ======================================================

        function loadSourceLines(return_type, source_id) {
            $.ajax({
                url: url_local + '/admin/purchase-return/source-lines/' + return_type + '/' + source_id,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#productRows').html(`
                        <tr>
                            <td colspan="13" class="text-center">
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
            $('#supplier_name').val(header.supplier_name ?? '');
            $('#warehouse_name').val(header.warehouse_name ?? '');
        }

        function bindProductRows(lines) {
            $('#productRows').html('');

            if (!lines || !lines.length) {
                $('#productRows').html(`
                    <tr id="emptyRow">
                        <td colspan="13" class="text-center text-muted">
                            Nothing remaining to return for this source.
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
                        <input type="hidden" name="products[][purchase_detail_id]" value="${line.purchase_detail_id}">
                        <input type="hidden" name="products[][good_receipt_note_detail_id]" value="${line.good_receipt_note_detail_id ?? ''}">
                        <input type="hidden" name="products[][reason]" value="">
                        ${line.product_name}
                    </td>
                    <td>${line.product_variation_name}</td>
                    <td>${line.batch_no ? line.batch_no + (line.expiry_date ? ' (exp. ' + line.expiry_date + ')' : '') : '-'}</td>
                    <td class="serial-cell" data-track-serial="${line.track_serial_number ? 1 : 0}" data-purchase-detail-id="${line.purchase_detail_id}">
                        <button type="button" class="btn btn-sm btn-outline-primary serial-entry-btn" style="${line.track_serial_number ? '' : 'display:none;'}">
                            <i class="fa fa-list-check"></i> <span class="serial-count-label">Select Serials (0/0)</span>
                        </button>
                        <span class="serial-na text-muted" style="${line.track_serial_number ? 'display:none;' : ''}">N/A</span>
                        <div class="serial-hidden-inputs" style="display:none;"></div>
                    </td>
                    <td>${line.unit_name}</td>
                    <td class="received-qty">${decimal(line.received_quantity)}</td>
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
            row.find('input[name*="[purchase_detail_id]"]').attr('name',
                `products[${$('#productRows tr.product-row').length}][purchase_detail_id]`);
            row.find('input[name*="[good_receipt_note_detail_id]"]').attr('name',
                `products[${$('#productRows tr.product-row').length}][good_receipt_note_detail_id]`);

            $('#productRows').append(row);
            reindexRows();
            calculateRow(row.is(':last-child') ? $('#productRows tr.product-row').last() : row);

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
                $(this).find('input[name*="[purchase_detail_id]"]').attr('name',
                    `products[${index}][purchase_detail_id]`);
                $(this).find('input[name*="[good_receipt_note_detail_id]"]').attr('name',
                    `products[${index}][good_receipt_note_detail_id]`);
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
        // SERIAL NUMBER PICKER MODAL (select from what's actually available
        // for this purchase/GRN line, rather than freely typed like receiving)
        // ======================================================
        var prSerialModal = null;
        var currentPrSerialRow = null;

        $(document).on('click', '.serial-entry-btn', function() {
            currentPrSerialRow = $(this).closest('tr');
            let cell = currentPrSerialRow.find('.serial-cell');
            let purchaseDetailId = cell.data('purchase-detail-id');
            let expected = decimal(currentPrSerialRow.find('.return-qty').val()) || 0;
            let alreadyChecked = currentPrSerialRow.find('.serial-hidden-input').map(function() {
                return $(this).val();
            }).get();

            $('#prSerialModalHint').text(`Select exactly ${expected} serial number(s) to return.`);
            $('#prSerialModalList').html('<div class="text-muted">Loading...</div>');
            prSerialModal = prSerialModal || new bootstrap.Modal(document.getElementById('prSerialModal'));
            prSerialModal.show();

            $.ajax({
                url: url_local + '/admin/purchase-return/available-serials/' + purchaseDetailId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response.Success || !response.Data.length) {
                        $('#prSerialModalList').html('<div class="text-muted">No available serial numbers found for this line.</div>');
                        return;
                    }
                    let html = '';
                    $.each(response.Data, function(_, s) {
                        let checked = alreadyChecked.includes(s.serial_no) ? 'checked' : '';
                        html += `
                            <div class="form-check">
                                <input class="form-check-input pr-serial-checkbox" type="checkbox" value="${s.serial_no}" id="prSerial_${s.product_variation_serial_number_id}" ${checked}>
                                <label class="form-check-label" for="prSerial_${s.product_variation_serial_number_id}">${s.serial_no}</label>
                            </div>
                        `;
                    });
                    $('#prSerialModalList').html(html);
                },
                error: function() {
                    $('#prSerialModalList').html('<div class="text-danger">Unable to load serial numbers.</div>');
                }
            });
        });

        $('#prSerialModalSaveBtn').on('click', function() {
            if (!currentPrSerialRow) return;
            let selected = $('.pr-serial-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            let expected = parseFloat(currentPrSerialRow.find('.return-qty').val()) || 0;
            if (selected.length !== expected) {
                errorMessage(`Select exactly ${expected} serial number(s) (currently ${selected.length}).`);
                return;
            }

            currentPrSerialRow.find('.serial-hidden-inputs').html(
                selected.map(sn => `<input type="hidden" class="serial-hidden-input" value="${sn}">`).join('')
            );
            reindexRows();
            refreshSerialButtonLabel(currentPrSerialRow);
            bootstrap.Modal.getInstance(document.getElementById('prSerialModal')).hide();
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

        function loadPurchaseReturnForEdit() {
            if (!editPurchaseReturnData || !editPurchaseReturnData.details || !editPurchaseReturnData.details
                .length) {
                return;
            }

            bindSourceHeader({
                supplier_name: $('#supplier_name').val(),
                warehouse_name: $('#warehouse_name').val()
            });

            $('#productRows').html('');

            $.each(editPurchaseReturnData.details, function(_, item) {
                addProductRow({
                    purchase_detail_id: item.purchase_detail_id,
                    good_receipt_note_detail_id: item.good_receipt_note_detail_id,
                    product_name: item.product_name,
                    product_variation_name: item.product_variation_name,
                    received_quantity: item.received_quantity,
                    already_returned_quantity: item.already_returned_quantity,
                    returnable_quantity: (decimal(item.received_quantity) - decimal(item
                        .already_returned_quantity)) + decimal(item.return_quantity),
                    unit_name: item.unit_name,
                    unit_price: item.unit_price,
                    conversion_factor: item.conversion_factor,
                    discount: item.discount,
                    tax: item.tax,
                    batch_no: item.batch_no,
                    expiry_date: item.expiry_date,
                    track_serial_number: item.track_serial_number,
                    serial_numbers: item.serial_numbers || []
                }, item.return_quantity);
            });

            calculateGrandTotal();
        }

        // ======================================================
        // FORM SUBMIT
        // ======================================================

        $('#purchaseReturnForm').on('submit', function(e) {
            if ($('#productRows tr.product-row').length == 0) {
                e.preventDefault();
                errorMessage('Please select a purchase or GRN with returnable products.');
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
    <div class="modal fade" id="prSerialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('purchase_returns.select_serials_to_return') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2" id="prSerialModalHint">{{ __('purchase_returns.select_serials_hint') }}</p>
                    <div id="prSerialModalList" style="max-height:300px; overflow-y:auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="prSerialModalSaveBtn">{{ __('common.save') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
