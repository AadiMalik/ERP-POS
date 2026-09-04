@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($grn) ? 'Update' : 'New' }} Goods Receipt Note</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($grn) ? 'Update' : 'Create' }} Goods Receipt Note</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/good-receipt-note') }}" method="POST" id="grnForm">
                    @csrf
                    <input type="hidden" name="good_receipt_note_id" value="{{ $grn->good_receipt_note_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $grn->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>
                                Purchase<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="purchase_id" id="purchase_id"
                                {{ isset($grn) ? 'disabled' : '' }}>
                                <option value="">--Select Purchase--</option>
                                @foreach ($purchases as $item)
                                    <option value="{{ $item->purchase_id }}"
                                        {{ old('purchase_id', $grn->purchase_id ?? '') == $item->purchase_id ? 'selected' : '' }}>
                                        {{ $item->purchase_no }} - {{ $item->supplier->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($grn))
                                <input type="hidden" name="purchase_id" value="{{ $grn->purchase_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Supplier</label>
                            <input type="text" class="form-control" id="supplier_name" readonly
                                value="{{ $grn->supplier->name ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Warehouse</label>
                            <input type="text" class="form-control" id="warehouse_name" readonly
                                value="{{ $grn->warehouse->name ?? '' }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>GRN Number</label>
                            <input type="text" class="form-control" name="good_receipt_note_no" readonly
                                value="{{ $grn->good_receipt_note_no ?? ($good_receipt_note_no ?? 'Auto Generated') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>GRN Date</label>
                            <input type="text" class="form-control datepicker" name="good_receipt_note_date"
                                value="{{ old('good_receipt_note_date', isset($grn) ? localDate($grn->good_receipt_note_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-12">
                            <label>Description</label>
                            <textarea class="form-control" rows="3" name="description">{{ old('description', $grn->description ?? '') }}</textarea>
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
                                        <th style="min-width:120px;">Already Received</th>
                                        <th style="min-width:110px;">Remaining</th>
                                        <th style="min-width:130px;">Receive Qty</th>
                                        <th style="min-width:130px;">Batch No</th>
                                        <th style="min-width:150px;">Expiry Date</th>
                                        <th style="min-width:160px;">Serial #</th>
                                        <th style="min-width:120px;">Unit Price</th>
                                        <th style="min-width:130px">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    <tr id="emptyRow">
                                        <td colspan="12" class="text-center text-muted">
                                            Select a Purchase
                                        </td>
                                    </tr>
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
                            <button class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($grn) ? 'Update GRN' : 'Save GRN' }}
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
        var isEditMode = {{ isset($grn) ? 'true' : 'false' }};
        var editGrnData = @json($grn_details ?? null);

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            if (isEditMode) {
                loadGrnForEdit();
            } else if ($('#purchase_id').val()) {
                loadPurchaseLines($('#purchase_id').val());
            }
        });

        // ======================================================
        // PURCHASE CHANGE
        // ======================================================

        $(document).on('change', '#purchase_id', function() {
            let purchase_id = $(this).val();

            if (!purchase_id) {
                resetProductRows();
                return;
            }

            loadPurchaseLines(purchase_id);
        });

        function resetProductRows() {
            $('#supplier_name').val('');
            $('#warehouse_name').val('');
            $('#productRows').html(`
                <tr id="emptyRow">
                    <td colspan="12" class="text-center text-muted">
                        Select a Purchase
                    </td>
                </tr>
            `);
            calculateGrandTotal();
        }

        // ======================================================
        // LOAD PURCHASE RECEIVABLE LINES (create mode)
        // ======================================================

        function loadPurchaseLines(purchase_id) {
            $.ajax({
                url: url_local + '/admin/good-receipt-note/purchase-details/' + purchase_id,
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
                    bindPurchaseHeader(response.Data.header);
                    bindProductRows(response.Data.lines);
                },
                error: function() {
                    errorMessage('Unable to load purchase lines.');
                }
            });
        }

        function bindPurchaseHeader(header) {
            if (!header) return;
            $('#supplier_name').val(header.supplier_name ?? '');
            $('#warehouse_name').val(header.warehouse_name ?? '');
        }

        function bindProductRows(lines) {
            $('#productRows').html('');

            if (!lines || !lines.length) {
                $('#productRows').html(`
                    <tr id="emptyRow">
                        <td colspan="12" class="text-center text-muted">
                            Nothing remaining to receive for this purchase.
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

        function addProductRow(line, received_quantity) {
            received_quantity = received_quantity ?? 0;

            let showBatch = line.track_batch == true || line.track_batch == 1;
            let showExpiry = line.track_expiry == true || line.track_expiry == 1;
            let showSerial = line.track_serial_number == true || line.track_serial_number == 1;
            let serialNumbers = line.serial_numbers || [];
            let serialInputsHtml = serialNumbers.map(sn =>
                `<input type="hidden" class="serial-hidden-input" name="products[${$('#productRows tr.product-row').length}][serial_numbers][]" value="${sn}">`
            ).join('');

            let row = $(`
                <tr class="product-row">
                    <td>
                        <input type="hidden" name="products[][purchase_detail_id]" value="${line.purchase_detail_id}">
                        ${line.product_name}
                    </td>
                    <td>${line.product_variation_name}</td>
                    <td>${line.unit_name}</td>
                    <td class="ordered-qty">${decimal(line.ordered_quantity)}</td>
                    <td class="already-received-qty">${decimal(line.already_received_quantity)}</td>
                    <td class="remaining-qty">${decimal(line.remaining_quantity)}</td>
                    <td>
                        <input type="text" class="form-control receive-qty"
                            name="products[${$('#productRows tr.product-row').length}][received_quantity]"
                            value="${decimal(received_quantity)}"
                            data-remaining="${line.remaining_quantity}">
                    </td>
                    <td class="batch-cell">
                        <input type="text" class="form-control batch-no" name="products[${$('#productRows tr.product-row').length}][batch_no]"
                            value="${line.batch_no ?? ''}" placeholder="Batch No." style="${showBatch ? '' : 'display:none;'}">
                        <span class="batch-no-na text-muted" style="${showBatch ? 'display:none;' : ''}">N/A</span>
                    </td>
                    <td class="expiry-cell">
                        <input type="text" class="form-control datepicker expiry-date" name="products[${$('#productRows tr.product-row').length}][expiry_date]"
                            value="${line.expiry_date ?? ''}" placeholder="Expiry Date" style="${showExpiry ? '' : 'display:none;'}">
                        <span class="expiry-date-na text-muted" style="${showExpiry ? 'display:none;' : ''}">N/A</span>
                    </td>
                    <td class="serial-cell" data-track-serial="${showSerial ? 1 : 0}">
                        <button type="button" class="btn btn-sm btn-outline-primary serial-entry-btn" style="${showSerial ? '' : 'display:none;'}">
                            <i class="fa fa-barcode"></i> <span class="serial-count-label">Enter Serials (${serialNumbers.length}/0)</span>
                        </button>
                        <span class="serial-na text-muted" style="${showSerial ? 'display:none;' : ''}">N/A</span>
                        <div class="serial-hidden-inputs" style="display:none;">${serialInputsHtml}</div>
                    </td>
                    <td class="unit-price">${decimal(line.unit_price)}</td>
                    <td class="row-total">${decimal(received_quantity * (line.conversion_factor || 1) * line.unit_price)}</td>
                </tr>
            `);

            row.data('unit_price', line.unit_price);
            row.data('conversion_factor', line.conversion_factor || 1);
            row.find('input[name*="[purchase_detail_id]"]').attr('name',
                `products[${$('#productRows tr.product-row').length}][purchase_detail_id]`);

            $('#productRows').append(row);
            reindexRows();
            refreshSerialButtonLabel(row);

            if (showExpiry) {
                let expiryInput = row.find('.expiry-date').get(0);
                if (expiryInput && typeof flatpickr === 'function' && !expiryInput._flatpickr) {
                    flatpickr(expiryInput, {
                        dateFormat: "{{ session('business_setting.date_format', 'd-m-Y') }}",
                        allowInput: true
                    });
                }
            }
        }

        function reindexRows() {
            $('#productRows tr.product-row').each(function(index) {
                $(this).find('input[name*="[purchase_detail_id]"]').attr('name', `products[${index}][purchase_detail_id]`);
                $(this).find('input.receive-qty').attr('name', `products[${index}][received_quantity]`);
                $(this).find('input.batch-no').attr('name', `products[${index}][batch_no]`);
                $(this).find('input.expiry-date').attr('name', `products[${index}][expiry_date]`);
                $(this).find('input.serial-hidden-input').attr('name', `products[${index}][serial_numbers][]`);
            });
        }

        function refreshSerialButtonLabel(row) {
            const entered = row.find('.serial-hidden-input').length;
            const expected = decimal(row.find('.receive-qty').val()) || 0;
            row.find('.serial-count-label').text(`Enter Serials (${entered}/${expected})`);
            row.find('.serial-entry-btn').toggleClass('btn-outline-primary', entered == expected)
                .toggleClass('btn-outline-danger', entered != expected);
        }

        // ======================================================
        // RECEIVE QTY CHANGE
        // ======================================================

        $(document).on('keyup change', '.receive-qty', function() {
            let row = $(this).closest('tr');
            let remaining = decimal($(this).data('remaining'));
            let qty = decimal($(this).val());

            if (qty > remaining) {
                qty = remaining;
                $(this).val(decimal(qty));
            }
            if (qty < 0) {
                qty = 0;
                $(this).val(decimal(qty));
            }

            let unitPrice = decimal(row.data('unit_price'));
            let conversionFactor = decimal(row.data('conversion_factor')) || 1;
            let total = qty * conversionFactor * unitPrice;

            row.find('.row-total').html(decimal(total));
            row.data('total', decimal(total));

            refreshSerialButtonLabel(row);
            calculateGrandTotal();
        });

        // ======================================================
        // SERIAL NUMBER ENTRY MODAL
        // ======================================================
        var grnSerialEntryModal = null;
        var currentGrnSerialRow = null;

        $(document).on('click', '.serial-entry-btn', function() {
            currentGrnSerialRow = $(this).closest('tr');
            const existing = currentGrnSerialRow.find('.serial-hidden-input').map(function() {
                return $(this).val();
            }).get();
            $('#grnSerialModalTextarea').val(existing.join('\n'));
            const expected = decimal(currentGrnSerialRow.find('.receive-qty').val()) || 0;
            $('#grnSerialModalHint').text(`Enter exactly ${expected} serial number(s), one per line, matching the receive quantity.`);
            grnSerialEntryModal = grnSerialEntryModal || new bootstrap.Modal(document.getElementById('grnSerialEntryModal'));
            grnSerialEntryModal.show();
        });

        $(document).on('change', '#grnSerialScanHelperInput', function() {
            const code = $(this).val();
            if (!code) return;
            const ta = $('#grnSerialModalTextarea');
            const current = ta.val();
            ta.val(current && !current.endsWith('\n') ? current + '\n' + code : current + code);
            $(this).val('');
        });

        $('#grnSerialModalSaveBtn').on('click', function() {
            if (!currentGrnSerialRow) return;
            const lines = $('#grnSerialModalTextarea').val()
                .split('\n')
                .map(s => s.trim())
                .filter(s => s !== '');

            const duplicates = lines.filter((v, i) => lines.indexOf(v) !== i);
            if (duplicates.length) {
                errorMessage('Duplicate serial number(s) entered: ' + [...new Set(duplicates)].join(', '));
                return;
            }

            currentGrnSerialRow.find('.serial-hidden-inputs').html(
                lines.map(sn => `<input type="hidden" class="serial-hidden-input" value="${sn.replace(/"/g, '&quot;')}">`).join('')
            );
            reindexRows();
            refreshSerialButtonLabel(currentGrnSerialRow);
            bootstrap.Modal.getInstance(document.getElementById('grnSerialEntryModal')).hide();
        });

        $(document).on('blur', '.receive-qty', function() {
            $(this).val(decimal($(this).val()));
        });

        // ======================================================
        // GRAND TOTAL
        // ======================================================

        function calculateGrandTotal() {
            let total = 0;
            $('#productRows tr.product-row').each(function() {
                total += decimal($(this).data('total'));
            });
            $('#total').val(decimal(total));
        }

        // ======================================================
        // EDIT MODE
        // ======================================================

        function loadGrnForEdit() {
            if (!editGrnData || !editGrnData.details || !editGrnData.details.length) {
                return;
            }

            bindPurchaseHeader({
                supplier_name: $('#supplier_name').val(),
                warehouse_name: $('#warehouse_name').val()
            });

            $('#productRows').html('');

            $.each(editGrnData.details, function(_, item) {
                addProductRow({
                    purchase_detail_id: item.purchase_detail_id,
                    product_name: item.product_name,
                    product_variation_name: item.product_variation_name,
                    ordered_quantity: item.ordered_quantity,
                    already_received_quantity: item.already_received_quantity,
                    remaining_quantity: item.remaining_quantity,
                    unit_name: item.unit_name,
                    unit_price: item.unit_price,
                    conversion_factor: item.conversion_factor,
                    track_batch: item.track_batch,
                    track_expiry: item.track_expiry,
                    track_serial_number: item.track_serial_number,
                    batch_no: item.batch_no,
                    expiry_date: item.expiry_date,
                    serial_numbers: item.serial_numbers || []
                }, item.received_quantity);
            });

            calculateGrandTotal();
        }

        // ======================================================
        // FORM SUBMIT
        // ======================================================

        $('#grnForm').on('submit', function(e) {
            if ($('#productRows tr.product-row').length == 0) {
                e.preventDefault();
                errorMessage('Please select a purchase with receivable products.');
                return false;
            }

            let hasQuantity = false;
            $('#productRows .receive-qty').each(function() {
                if (decimal($(this).val()) > 0) {
                    hasQuantity = true;
                }
            });

            if (!hasQuantity) {
                e.preventDefault();
                errorMessage('Please receive at least one product.');
                return false;
            }

            let serialMismatch = false;
            $('#productRows tr.product-row').each(function() {
                let row = $(this);
                if (row.find('.serial-cell').data('track-serial') != 1) {
                    return;
                }
                let receiveQty = parseFloat(row.find('.receive-qty').val()) || 0;
                let enteredCount = row.find('.serial-hidden-input').length;
                if (receiveQty > 0 && enteredCount !== receiveQty) {
                    errorMessage(`Enter exactly ${receiveQty} serial number(s) for "${row.find('td').eq(0).text().trim()}" (currently ${enteredCount}).`);
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

    {{-- ================= SERIAL NUMBER ENTRY MODAL ================= --}}
    <div class="modal fade" id="grnSerialEntryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enter Serial Numbers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2" id="grnSerialModalHint">One serial number per line.</p>
                    <textarea class="form-control" id="grnSerialModalTextarea" rows="8"
                        placeholder="Scan or type serial numbers, one per line"></textarea>
                    <div class="mt-2">
                        <input type="text" class="form-control d-none" id="grnSerialScanHelperInput">
                        @include('admin.partials.barcode_scanner', ['targetInputId' => '#grnSerialScanHelperInput'])
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="grnSerialModalSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
@endsection
