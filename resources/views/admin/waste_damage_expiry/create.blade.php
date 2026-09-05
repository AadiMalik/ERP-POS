@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($waste_damage_expiry) ? 'Update' : 'New' }} {{ __('waste_damage_expiry.title') }}</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($waste_damage_expiry) ? 'Update' : 'Create' }} {{ __('waste_damage_expiry.title') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/waste-damage-expiry') }}" method="POST" id="wdeForm">
                    @csrf
                    <input type="hidden" name="waste_damage_expiry_id" value="{{ $waste_damage_expiry->waste_damage_expiry_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>{{ __('common.business') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">{{ __('common.select_business') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $waste_damage_expiry->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>Warehouse/Branch<span class="text-danger">*</span></label>
                            <select class="form-control select2" name="warehouse_id" id="warehouse_id"
                                {{ isset($waste_damage_expiry) ? 'disabled' : '' }}>
                                <option value="">{{ __('common.select_warehouse') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}"
                                        {{ old('warehouse_id', $waste_damage_expiry->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($waste_damage_expiry))
                                <input type="hidden" name="warehouse_id" value="{{ $waste_damage_expiry->warehouse_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('waste_damage_expiry.reference_no') }}</label>
                            <input type="text" class="form-control" name="reference_no" readonly
                                value="{{ $waste_damage_expiry->reference_no ?? ($reference_no ?? '{{ __('common.auto_generated') }}') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('common.date') }}</label>
                            <input type="text" class="form-control datepicker" name="transaction_date"
                                value="{{ old('transaction_date', isset($waste_damage_expiry) ? localDate($waste_damage_expiry->transaction_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>{{ __('waste_damage_expiry.reference_optional') }}</label>
                            <input type="text" class="form-control" name="reference" placeholder="{{ __('waste_damage_expiry.reference_placeholder') }}"
                                value="{{ old('reference', $waste_damage_expiry->reference ?? '') }}">
                        </div>
                        <div class="col-md-9 mb-3">
                            <label>{{ __('common.notes') }}</label>
                            <textarea class="form-control" rows="1" name="notes">{{ old('notes', $waste_damage_expiry->notes ?? '') }}</textarea>
                        </div>
                    </div>
                    <hr>
                    {{-- ================= LINE ITEMS ================= --}}
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">{{ __('common.products') }}</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="addProductBtn">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="productTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:200px;">{{ __('common.product') }}</th>
                                        <th style="min-width:150px;">{{ __('common.variation') }}</th>
                                        <th style="min-width:80px;">{{ __('common.unit') }}</th>
                                        <th style="min-width:100px;">Available Qty</th>
                                        <th style="min-width:170px;">{{ __('waste_damage_expiry.batch_lot') }}</th>
                                        <th style="min-width:130px;">{{ __('common.expiry_date') }}</th>
                                        <th style="min-width:110px;">{{ __('common.quantity') }}</th>
                                        <th style="min-width:160px;">{{ __('common.serial_number') }}</th>
                                        <th style="min-width:120px;">Value</th>
                                        <th style="min-width:130px;">{{ __('waste_damage_expiry.loss_type') }}</th>
                                        <th style="min-width:170px;">{{ __('common.reason') }}</th>
                                        <th style="min-width:150px;">{{ __('common.notes') }}</th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    <tr id="emptyRow">
                                        <td colspan="13" class="text-center text-muted">
                                            {{ __('waste_damage_expiry.select_warehouse_then') }} "Add Product"
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
                                    <th>Total Quantity</th>
                                    <td><input class="form-control" id="total_quantity" readonly></td>
                                </tr>
                                <tr>
                                    <th>Total Value</th>
                                    <td><input class="form-control fw-bold" id="total_value" readonly></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($waste_damage_expiry) ? 'Update' : 'Save' }}
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
        <script>errorMessage("{{ $errors->first() }}");</script>
    @endif
    @if (session('error'))
        <script>errorMessage("{{ session('error') }}");</script>
    @endif
    <script>
        var isEditMode = {{ isset($waste_damage_expiry) ? 'true' : 'false' }};
        var editData = @json($waste_damage_expiry_details ?? null);
        var productsData = @json($products);
        var lossReasonsData = @json($loss_reasons);
        var lossTypesData = @json($loss_types);
        var rowIndex = 0;

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({ width: '100%' });
            }
            if (isEditMode) {
                loadForEdit();
            }
        });

        function currentWarehouseId() {
            return $('#warehouse_id').val();
        }

        $(document).on('change', '#warehouse_id', function() {
            resetProductRows();
        });

        function resetProductRows() {
            $('#productRows').html(`
                <tr id="emptyRow">
                    <td colspan="13" class="text-center text-muted">{{ __('waste_damage_expiry.select_warehouse_then') }} "Add Product"</td>
                </tr>
            `);
            calculateGrandTotal();
        }

        function lossTypeOptions(selected) {
            let html = '';
            $.each(lossTypesData, function(value, label) {
                html += `<option value="${value}" ${selected == value ? 'selected' : ''}>${label}</option>`;
            });
            return html;
        }

        function lossReasonOptions(selected) {
            let html = '<option value="">--None--</option>';
            $.each(lossReasonsData, function(_, item) {
                html += `<option value="${item.loss_reason_id}" ${selected == item.loss_reason_id ? 'selected' : ''}>${item.name}</option>`;
            });
            return html;
        }

        // ======================================================
        // ADD PRODUCT ROW
        // ======================================================

        $('#addProductBtn').on('click', function() {
            if (!currentWarehouseId()) {
                errorMessage('Please select a warehouse first.');
                return;
            }
            addProductRow();
        });

        function addProductRow(prefill) {
            $('#emptyRow').remove();
            const index = rowIndex;

            let row = $(`
        <tr class="product-row">
            <td>
                <select name="lines[${index}][product_id]" class="form-control manual-product-select"></select>
            </td>
            <td>
                <select name="lines[${index}][product_variation_id]" class="form-control manual-variation-select"></select>
            </td>
            <td>
                <input type="hidden" class="selected-unit-id" name="lines[${index}][unit_id]" value="">
                <span class="selected-unit-name">-</span>
            </td>
            <td class="available-qty">-</td>
            <td>
                <select class="form-control batch-select">
                    <option value="">--No Batch--</option>
                </select>
                <input type="hidden" class="selected-batch-id" name="lines[${index}][product_variation_batch_id]" value="">
            </td>
            <td>
                <input type="date" class="form-control expiry-date" name="lines[${index}][expiry_date]" value="">
            </td>
            <td>
                <input type="text" class="form-control quantity" name="lines[${index}][quantity]" value="0">
            </td>
            <td class="serial-cell">
                <button type="button" class="btn btn-sm btn-outline-primary serial-entry-btn" style="display:none;">
                    <i class="fa fa-list-check"></i> <span class="serial-count-label">Select Serials (0/0)</span>
                </button>
                <span class="serial-na text-muted">N/A</span>
                <div class="serial-hidden-inputs" style="display:none;"></div>
            </td>
            <td class="row-value">${decimal(0)}</td>
            <td>
                <select class="form-control loss-type" name="lines[${index}][loss_type]">
                    ${lossTypeOptions('')}
                </select>
            </td>
            <td>
                <select class="form-control loss-reason" name="lines[${index}][loss_reason_id]">
                    ${lossReasonOptions('')}
                </select>
            </td>
            <td>
                <input type="text" class="form-control line-notes" name="lines[${index}][notes]" value="">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-row"><i class="fa fa-trash"></i></button>
            </td>
        </tr>
    `);

            row.data('unit_cost', 0);
            row.data('available_quantity', 0);
            $('#productRows').append(row);
            loadManualProductDropdown(row, prefill);
            rowIndex++;

            if (prefill && prefill.track_serial_number && prefill.serial_numbers && prefill.serial_numbers.length) {
                setTimeout(function() {
                    row.find('.serial-hidden-inputs').html(
                        prefill.serial_numbers.map(sn => `<input type="hidden" class="serial-hidden-input" name="${row.find('.quantity').attr('name').replace('[quantity]', '[serial_numbers][]')}" value="${sn}">`).join('')
                    );
                    refreshSerialButtonLabel(row);
                }, 600);
            }
        }

        function refreshSerialButtonLabel(row) {
            const entered = row.find('.serial-hidden-input').length;
            const expected = decimal(row.find('.quantity').val()) || 0;
            row.find('.serial-count-label').text(`Select Serials (${entered}/${expected})`);
            row.find('.serial-entry-btn').toggleClass('btn-outline-primary', entered == expected)
                .toggleClass('btn-outline-danger', entered != expected);
        }

        function loadManualProductDropdown(row, prefill) {
            let html = `<option value="">{{ __('common.select_product') }}</option>`;
            $.each(productsData, function(_, product) {
                html += `<option value="${product.product_id}" ${prefill && prefill.product_id == product.product_id ? 'selected' : ''}>${product.name}</option>`;
            });
            row.find('.manual-product-select').html(html);

            if (prefill && prefill.product_id) {
                loadVariations(row, prefill.product_id, prefill);
            }
        }

        $(document).on('change', '.manual-product-select', function() {
            let row = $(this).closest('tr');
            let productId = $(this).val();
            resetRowVariationDependents(row);
            if (productId) {
                loadVariations(row, productId, null);
            }
        });

        function resetRowVariationDependents(row) {
            row.find('.manual-variation-select').html('<option value="">{{ __('common.select_variation') }}</option>');
            row.find('.selected-unit-id').val('');
            row.find('.selected-unit-name').html('-');
            row.find('.available-qty').html('-');
            row.find('.batch-select').html('<option value="">--No Batch--</option>');
            row.find('.selected-batch-id').val('');
            row.find('.expiry-date').val('').prop('readonly', false);
            row.data('unit_cost', 0);
            row.data('available_quantity', 0);
            calculateRow(row);
        }

        function loadVariations(row, productId, prefill) {
            $.ajax({
                url: url_local + '/admin/product/variation-by-product/' + productId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    row.find('.manual-variation-select').html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">{{ __('common.select_variation') }}</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, variation) {
                            let selected = prefill && prefill.product_variation_id == variation.product_variation_id ? 'selected' : '';
                            html += `<option value="${variation.product_variation_id}"
                                data-unit-id="${variation.unit?.unit_id ?? variation.base_unit_id ?? ''}"
                                data-unit-name="${variation.unit?.name ?? ''}"
                                data-track-serial="${variation.track_serial_number ? 1 : 0}" ${selected}>${variation.name}</option>`;
                        });
                    }
                    row.find('.manual-variation-select').html(html);
                    if (prefill && prefill.product_variation_id) {
                        row.find('.manual-variation-select').trigger('change');
                    }
                },
                error: function() {
                    errorMessage('Unable to load variations.');
                }
            });
        }

        $(document).on('change', '.manual-variation-select', function() {
            let row = $(this).closest('tr');
            let option = $(this).find(':selected');
            let variationId = $(this).val();

            row.find('.selected-unit-id').val(option.data('unit-id') || '');
            row.find('.selected-unit-name').html(option.data('unit-name') || '-');
            row.find('.batch-select').html('<option value="">--No Batch--</option>');
            row.find('.selected-batch-id').val('');
            row.data('unit_cost', 0);
            row.data('available_quantity', 0);
            row.find('.available-qty').html('-');

            let trackSerial = option.data('track-serial') == 1;
            row.data('track-serial', trackSerial);
            row.find('.serial-hidden-inputs').empty();
            row.find('.serial-entry-btn').toggle(trackSerial);
            row.find('.serial-na').toggle(!trackSerial);
            refreshSerialButtonLabel(row);

            let warehouseId = currentWarehouseId();
            if (!variationId || !warehouseId) {
                calculateRow(row);
                return;
            }

            $.ajax({
                url: url_local + '/admin/waste-damage-expiry/stock/' + warehouseId + '/' + variationId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.Success) {
                        row.data('unit_cost', decimal(response.Data.avg_price));
                        row.data('available_quantity', decimal(response.Data.available_quantity));
                        row.find('.available-qty').html(decimal(response.Data.available_quantity));
                    }
                    calculateRow(row);
                }
            });

            $.ajax({
                url: url_local + '/admin/waste-damage-expiry/batches/' + warehouseId + '/' + variationId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let html = '<option value="">--No Batch--</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, batch) {
                            html += `<option value="${batch.product_variation_batch_id}"
                                data-qty="${batch.quantity}" data-price="${batch.avg_price}"
                                data-expiry="${batch.expiry_date ?? ''}" data-batch-no="${batch.batch_no}">
                                ${batch.batch_no} (Qty: ${decimal(batch.quantity)}${batch.expiry_date ? ', Exp: ' + batch.expiry_date : ''})
                                </option>`;
                        });
                    }
                    row.find('.batch-select').html(html);
                }
            });

            row.data('serial-warehouse-id', warehouseId);
            row.data('serial-variation-id', variationId);
        });

        $(document).on('change', '.batch-select', function() {
            let row = $(this).closest('tr');
            let option = $(this).find(':selected');
            let batchId = $(this).val();

            row.find('.selected-batch-id').val(batchId || '');

            if (batchId) {
                row.data('unit_cost', decimal(option.data('price')));
                row.data('available_quantity', decimal(option.data('qty')));
                row.find('.available-qty').html(decimal(option.data('qty')));
                row.find('.expiry-date').val(option.data('expiry') || '').prop('readonly', true);
            } else {
                row.find('.expiry-date').prop('readonly', false);
            }
            calculateRow(row);
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            if ($('#productRows tr.product-row').length == 0) {
                resetProductRows();
            }
            calculateGrandTotal();
        });

        // ======================================================
        // ROW / GRAND TOTAL CALCULATION
        // ======================================================

        $(document).on('keyup change', '.quantity', function() {
            let row = $(this).closest('tr');
            calculateRow(row);
            refreshSerialButtonLabel(row);
        });

        // ======================================================
        // SERIAL NUMBER PICKER MODAL
        // ======================================================
        var wdeSerialModal = null;
        var currentWdeSerialRow = null;

        $(document).on('click', '.serial-entry-btn', function() {
            currentWdeSerialRow = $(this).closest('tr');
            let warehouseId = currentWdeSerialRow.data('serial-warehouse-id');
            let variationId = currentWdeSerialRow.data('serial-variation-id');
            let expected = decimal(currentWdeSerialRow.find('.quantity').val()) || 0;
            let alreadyChecked = currentWdeSerialRow.find('.serial-hidden-input').map(function() {
                return $(this).val();
            }).get();

            $('#wdeSerialModalHint').text(`Select exactly ${expected} serial number(s).`);
            $('#wdeSerialModalList').html('<div class="text-muted">Loading...</div>');
            wdeSerialModal = wdeSerialModal || new bootstrap.Modal(document.getElementById('wdeSerialModal'));
            wdeSerialModal.show();

            $.ajax({
                url: url_local + '/admin/waste-damage-expiry/serials/' + warehouseId + '/' + variationId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response.Success || !response.Data.length) {
                        $('#wdeSerialModalList').html('<div class="text-muted">No available serial numbers found.</div>');
                        return;
                    }
                    let html = '';
                    $.each(response.Data, function(_, s) {
                        let checked = alreadyChecked.includes(s.serial_no) ? 'checked' : '';
                        html += `
                            <div class="form-check">
                                <input class="form-check-input wde-serial-checkbox" type="checkbox" value="${s.serial_no}" id="wdeSerial_${s.product_variation_serial_number_id}" ${checked}>
                                <label class="form-check-label" for="wdeSerial_${s.product_variation_serial_number_id}">${s.serial_no}</label>
                            </div>
                        `;
                    });
                    $('#wdeSerialModalList').html(html);
                },
                error: function() {
                    $('#wdeSerialModalList').html('<div class="text-danger">Unable to load serial numbers.</div>');
                }
            });
        });

        $('#wdeSerialModalSaveBtn').on('click', function() {
            if (!currentWdeSerialRow) return;
            let selected = $('.wde-serial-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            let expected = parseFloat(currentWdeSerialRow.find('.quantity').val()) || 0;
            if (selected.length !== expected) {
                errorMessage(`Select exactly ${expected} serial number(s) (currently ${selected.length}).`);
                return;
            }

            let qtyInputName = currentWdeSerialRow.find('.quantity').attr('name');
            let serialInputName = qtyInputName.replace('[quantity]', '[serial_numbers][]');

            currentWdeSerialRow.find('.serial-hidden-inputs').html(
                selected.map(sn => `<input type="hidden" class="serial-hidden-input" name="${serialInputName}" value="${sn.replace(/"/g, '&quot;')}">`).join('')
            );
            refreshSerialButtonLabel(currentWdeSerialRow);
            wdeSerialModal.hide();
        });

        function calculateRow(row) {
            let unitCost = decimal(row.data('unit_cost'));
            let quantity = decimal(row.find('.quantity').val());
            let value = quantity * unitCost;

            row.find('.row-value').html(decimal(value));
            row.data('value', value);
            row.data('quantity', quantity);

            let available = decimal(row.data('available_quantity'));
            if (available > 0 && quantity > available) {
                row.find('.quantity').addClass('is-invalid').attr('title', 'Exceeds available quantity (' + decimal(available) + ')');
            } else {
                row.find('.quantity').removeClass('is-invalid').removeAttr('title');
            }

            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let total_quantity = 0;
            let total_value = 0;

            $('#productRows tr.product-row').each(function() {
                let row = $(this);
                total_quantity += decimal(row.data('quantity'));
                total_value += decimal(row.data('value'));
            });

            $('#total_quantity').val(decimal(total_quantity));
            $('#total_value').val(decimal(total_value));
        }

        // ======================================================
        // EDIT MODE
        // ======================================================

        function loadForEdit() {
            if (!editData || !editData.details || !editData.details.length) {
                return;
            }
            $('#productRows').html('');

            $.each(editData.details, function(_, item) {
                addProductRow({
                    product_id: item.product_id,
                    product_variation_id: item.product_variation_id,
                    track_serial_number: item.track_serial_number,
                    serial_numbers: item.serial_numbers,
                });

                let row = $('#productRows tr.product-row').last();
                row.find('.quantity').val(decimal(item.quantity));
                row.find('.loss-type').val(item.loss_type);
                row.find('.loss-reason').val(item.loss_reason_id || '');
                row.find('.line-notes').val(item.notes || '');
                if (item.expiry_date && !item.product_variation_batch_id) {
                    row.find('.expiry-date').val(item.expiry_date);
                }
                row.data('unit_cost', decimal(item.unit_cost));
                row.data('value', decimal(item.value));
                row.find('.row-value').html(decimal(item.value));
            });

            calculateGrandTotal();
        }

        // ======================================================
        // FORM SUBMIT
        // ======================================================

        $('#wdeForm').on('submit', function(e) {
            if ($('#productRows tr.product-row').length == 0) {
                e.preventDefault();
                errorMessage('Please add at least one product.');
                return false;
            }

            let serialMismatch = false;
            $('#productRows tr.product-row').each(function() {
                let row = $(this);
                if (!row.data('track-serial')) {
                    return;
                }
                let qty = parseFloat(row.find('.quantity').val()) || 0;
                let enteredCount = row.find('.serial-hidden-input').length;
                if (qty > 0 && enteredCount !== qty) {
                    errorMessage(`Select exactly ${qty} serial number(s) for this line (currently ${enteredCount}).`);
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
    <div class="modal fade" id="wdeSerialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Serial Numbers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2" id="wdeSerialModalHint">Select the serial numbers.</p>
                    <div id="wdeSerialModalList" style="max-height:300px; overflow-y:auto;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="wdeSerialModalSaveBtn">{{ __('common.save') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
