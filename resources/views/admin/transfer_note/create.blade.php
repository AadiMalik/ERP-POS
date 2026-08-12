@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($transfer_note) ? 'Update' : 'New' }} Transfer Note</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($transfer_note) ? 'Update' : 'Create' }} Transfer Note</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/transfer-note') }}" method="POST" id="transferNoteForm">
                    @csrf
                    <input type="hidden" name="transfer_note_id" value="{{ $transfer_note->transfer_note_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $transfer_note->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>
                                Source Warehouse<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="source_warehouse_id" id="source_warehouse_id"
                                {{ isset($transfer_note) ? 'disabled' : '' }}>
                                <option value="">--Select Warehouse--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}"
                                        {{ old('source_warehouse_id', $transfer_note->source_warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($transfer_note))
                                <input type="hidden" name="source_warehouse_id" value="{{ $transfer_note->source_warehouse_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>
                                Destination Warehouse<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="destination_warehouse_id" id="destination_warehouse_id"
                                {{ isset($transfer_note) ? 'disabled' : '' }}>
                                <option value="">--Select Warehouse--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}"
                                        data-business="{{ $item->business_id }}"
                                        {{ old('destination_warehouse_id', $transfer_note->destination_warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($transfer_note))
                                <input type="hidden" name="destination_warehouse_id" value="{{ $transfer_note->destination_warehouse_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Transfer Note No.</label>
                            <input type="text" class="form-control" name="transfer_note_no" readonly
                                value="{{ $transfer_note->transfer_note_no ?? ($transfer_note_no ?? 'Auto Generated') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Date</label>
                            <input type="text" class="form-control datepicker" name="transfer_note_date"
                                value="{{ old('transfer_note_date', isset($transfer_note) ? localDate($transfer_note->transfer_note_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Reference</label>
                            <input type="text" class="form-control" name="reference"
                                value="{{ old('reference', $transfer_note->reference ?? '') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Notes</label>
                            <textarea class="form-control" rows="1" name="description">{{ old('description', $transfer_note->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <hr>
                    {{-- ================= PRODUCT TABLE ================= --}}
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Products
                            </h5>
                            <button type="button" class="btn btn-sm btn-primary" id="addProductBtn">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="productTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px;">Product</th>
                                        <th style="min-width:150px;">Variation</th>
                                        <th style="min-width:170px;">Conversion</th>
                                        <th style="min-width:90px;">Unit</th>
                                        <th style="min-width:120px;">Available Qty</th>
                                        <th style="min-width:130px;">Transfer Qty</th>
                                        <th style="min-width:130px">Est. Value</th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    <tr id="emptyRow">
                                        <td colspan="8" class="text-center text-muted">
                                            Select a source warehouse, then "Add Product"
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
                                    <td>
                                        <input class="form-control" id="total_quantity" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total Value</th>
                                    <td>
                                        <input class="form-control fw-bold" id="total_value" readonly>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($transfer_note) ? 'Update Transfer Note' : 'Save Transfer Note' }}
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
        var isEditMode = {{ isset($transfer_note) ? 'true' : 'false' }};
        var editTransferNoteData = @json($transfer_note_details ?? null);
        var productsData = @json($products);
        var productIndex = 0;

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            if (isEditMode) {
                loadTransferNoteForEdit();
            }
        });

        // ======================================================
        // BUSINESS CHANGE (Super Admin only - reload warehouses/products
        // scoped to the selected business, since the server can only
        // preload data for the logged-in user's own business)
        // ======================================================

        $(document).on('change', '#business_id', function() {
            let businessId = $(this).val();

            $('#source_warehouse_id').html('<option value="">--Select Warehouse--</option>');
            $('#destination_warehouse_id').html('<option value="">--Select Warehouse--</option>');
            productsData = [];
            refreshProductDropdowns();
            resetProductRows();

            if (!businessId) {
                return;
            }

            loadWarehousesByBusiness(businessId);
            loadProductsByBusiness(businessId);
        });

        function loadWarehousesByBusiness(businessId) {
            $.ajax({
                url: url_local + '/admin/warehouse/by-business/' + businessId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#source_warehouse_id,#destination_warehouse_id').prop('disabled', true).html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select Warehouse--</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, warehouse) {
                            html += `<option value="${warehouse.warehouse_id}" data-business="${warehouse.business_id}">${warehouse.name}</option>`;
                        });
                    }
                    $('#source_warehouse_id,#destination_warehouse_id').html(html).prop('disabled', false);
                },
                error: function() {
                    $('#source_warehouse_id,#destination_warehouse_id').html('<option value="">--Select Warehouse--</option>').prop('disabled', false);
                    errorMessage('Unable to load warehouses.');
                }
            });
        }

        function loadProductsByBusiness(businessId) {
            $.ajax({
                url: url_local + '/admin/product/by-business/' + businessId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.Success) {
                        productsData = response.Data;
                        refreshProductDropdowns();
                    }
                },
                error: function() {
                    errorMessage('Unable to load products.');
                }
            });
        }

        function refreshProductDropdowns() {
            let options = `<option value="">--Select Product--</option>`;
            $.each(productsData, function(_, product) {
                options += `<option value="${product.product_id}">${product.name}</option>`;
            });

            $('.product-select').each(function() {
                let currentValue = $(this).val();
                $(this).html(options);
                if (currentValue) {
                    $(this).val(currentValue);
                }
            });
        }

        // ======================================================
        // SOURCE / DESTINATION GUARDS
        // ======================================================

        $(document).on('change', '#source_warehouse_id', function() {
            let sourceId = $(this).val();
            // Destination cannot be the same warehouse as source.
            $('#destination_warehouse_id option').show();
            $('#destination_warehouse_id option[value="' + sourceId + '"]').hide();
            if ($('#destination_warehouse_id').val() === sourceId) {
                $('#destination_warehouse_id').val('').trigger('change.select2');
            }
            resetProductRows();
        });

        function resetProductRows() {
            $('#productRows').html(`
        <tr id="emptyRow">
            <td colspan="8" class="text-center text-muted">
                Select a source warehouse, then "Add Product"
            </td>
        </tr>
    `);
            calculateGrandTotal();
        }

        // ======================================================
        // ADD PRODUCT ROW
        // ======================================================

        $('#addProductBtn').on('click', function() {
            if (!$('#source_warehouse_id').val()) {
                errorMessage('Please select a source warehouse first.');
                return;
            }
            addProductRow();
        });

        function getRowTemplate() {
            const index = productIndex;
            return `
    <tr class="product-row">
        <td>
            <select name="products[${index}][product_id]" class="form-control product-select">
                <option value="">--Select Product--</option>
            </select>
        </td>
        <td>
            <select name="products[${index}][product_variation_id]" class="form-control variation-select">
                <option value="">--Select Variation--</option>
            </select>
        </td>
        <td>
            <select name="products[${index}][product_variation_unit_conversion_id]" class="form-control conversion-select">
                <option value="">--Select Conversion--</option>
            </select>
            <input type="hidden" class="conversion-factor" name="products[${index}][conversion_factor]" value="1">
        </td>
        <td>
            <input type="hidden" class="selected-unit-id" name="products[${index}][unit_id]" value="">
            <span class="selected-unit-name">-</span>
        </td>
        <td class="available-qty">${decimal(0)}</td>
        <td>
            <input type="text" class="form-control transfer-qty" name="products[${index}][transfer_quantity]" value="0">
        </td>
        <td>
            <input type="text" class="form-control row-total" readonly value="0">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-danger remove-row">
                <i class="fa fa-trash"></i>
            </button>
        </td>
    </tr>
`;
        }

        function addProductRow() {
            $('#emptyRow').remove();
            $('#productRows').append(getRowTemplate());
            loadProductDropdown($('#productRows tr.product-row').last());
            productIndex++;
        }

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            if ($('#productRows tr.product-row').length == 0) {
                resetProductRows();
            }
            calculateGrandTotal();
        });

        // ======================================================
        // PRODUCT / VARIATION / CONVERSION
        // ======================================================

        function loadProductDropdown(row) {
            let html = `<option value="">--Select Product--</option>`;
            $.each(productsData, function(_, product) {
                html += `<option value="${product.product_id}">${product.name}</option>`;
            });
            row.find('.product-select').html(html);
        }

        $(document).on('change', '.product-select', function() {
            let row = $(this).closest('tr');
            let productId = $(this).val();
            resetVariationSection(row);
            if (!productId) {
                return;
            }
            loadVariations(productId, row);
        });

        function resetVariationSection(row) {
            row.find('.variation-select').html(`<option value="">--Select Variation--</option>`);
            row.find('.conversion-select').html(`<option value="">--Select Conversion--</option>`);
            row.find('.selected-unit-id').val('');
            row.find('.selected-unit-name').html('-');
            row.find('.conversion-factor').val(1);
            row.find('.available-qty').html(decimal(0));
            row.data('available_quantity', 0);
            row.data('unit_cost', 0);
        }

        function loadVariations(productId, row, onLoaded) {
            $.ajax({
                url: url_local + '/admin/product/variation-by-product/' + productId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    row.find('.variation-select').html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select Variation--</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, variation) {
                            html += `
                    <option
                        value="${variation.product_variation_id}"
                        data-unit-id="${variation.purchase_unit?.unit_id ?? ''}"
                        data-unit-name="${variation.purchase_unit?.name ?? ''}"
                    >
                        ${variation.name}
                    </option>
                `;
                        });
                    }
                    row.find('.variation-select').html(html);
                    if (typeof onLoaded === 'function') {
                        onLoaded(row);
                    }
                },
                error: function() {
                    errorMessage('Unable to load variations.');
                }
            });
        }

        $(document).on('change', '.variation-select', function() {
            let row = $(this).closest('tr');
            let variationId = $(this).val();
            let option = $(this).find(':selected');

            row.find('.selected-unit-id').val(option.data('unit-id'));
            row.find('.selected-unit-name').html(option.data('unit-name') || '-');
            row.find('.conversion-factor').val(1);
            row.find('.conversion-select').html(`<option value="">Loading...</option>`);

            if (!variationId) {
                row.find('.available-qty').html(decimal(0));
                row.data('available_quantity', 0);
                return;
            }

            loadConversions(variationId, row);
            fetchAvailableQuantity(row);
        });

        function loadConversions(variationId, row) {
            $.ajax({
                url: url_local + '/admin/product-variation-unit-conversion/by-variation/' + variationId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let html = `<option value="">--Select Conversion--</option>`;
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, conversion) {
                            html += `
                    <option
                        value="${conversion.product_variation_unit_conversion_id}"
                        data-factor="${conversion.conversion_factor}"
                        data-unit-id="${conversion.to_unit_id}"
                        data-unit-name="${conversion.to_unit?.name}"
                    >
                        ${conversion.from_unit?.name} → ${conversion.to_unit?.name} (${conversion.conversion_factor})
                    </option>
                `;
                        });
                    }
                    row.find('.conversion-select').html(html);
                },
                error: function() {
                    errorMessage('Unable to load conversions.');
                }
            });
        }

        $(document).on('change', '.conversion-select', function() {
            let row = $(this).closest('tr');
            let option = $(this).find(':selected');

            row.find('.conversion-factor').val(option.data('factor') || 1);
            row.find('.selected-unit-id').val(option.data('unit-id') || '');
            row.find('.selected-unit-name').html(option.data('unit-name') || '-');

            calculateRow(row);
        });

        // ======================================================
        // AVAILABLE QUANTITY (live, from source warehouse)
        // ======================================================

        function fetchAvailableQuantity(row) {
            let sourceWarehouseId = $('#source_warehouse_id').val();
            let productId = row.find('.product-select').val();
            let variationId = row.find('.variation-select').val();

            if (!sourceWarehouseId || !productId || !variationId) {
                return;
            }

            $.ajax({
                url: url_local + '/admin/transfer-note/source-stock/' + sourceWarehouseId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    let available = 0;
                    let unitCost = 0;
                    if (response.Success && response.Data.length) {
                        let match = response.Data.find(function(item) {
                            return item.product_id === productId && item.product_variation_id === variationId;
                        });
                        if (match) {
                            available = match.available_quantity;
                            unitCost = match.unit_cost;
                        }
                    }
                    row.find('.available-qty').html(decimal(available));
                    row.data('available_quantity', available);
                    row.data('unit_cost', unitCost);
                    calculateRow(row);
                },
                error: function() {
                    errorMessage('Unable to load available stock.');
                }
            });
        }

        // ======================================================
        // ROW CALCULATION
        // ======================================================

        $(document).on('keyup change', '.transfer-qty', function() {
            let row = $(this).closest('tr');
            let available = decimal(row.data('available_quantity'));
            let qty = decimal($(this).val());

            if (qty > available) {
                qty = available;
                $(this).val(decimal(qty));
                errorMessage('Transfer quantity cannot exceed the available stock at the source warehouse.');
            }
            if (qty < 0) {
                qty = 0;
                $(this).val(decimal(qty));
            }

            calculateRow(row);
        });

        function calculateRow(row) {
            let qty = decimal(row.find('.transfer-qty').val());
            let unitCost = decimal(row.data('unit_cost'));
            let conversionFactor = decimal(row.find('.conversion-factor').val()) || 1;

            let baseQty = qty * conversionFactor;
            let total = baseQty * unitCost;

            row.find('.row-total').val(decimal(total));
            row.data('total', total);

            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let total_quantity = 0;
            let total_value = 0;

            $('#productRows tr.product-row').each(function() {
                let row = $(this);
                let qty = decimal(row.find('.transfer-qty').val());
                let conversionFactor = decimal(row.find('.conversion-factor').val()) || 1;
                total_quantity += qty * conversionFactor;
                total_value += decimal(row.data('total'));
            });

            $('#total_quantity').val(decimal(total_quantity));
            $('#total_value').val(decimal(total_value));
        }

        // ======================================================
        // EDIT MODE
        // ======================================================

        function loadTransferNoteForEdit() {
            if (!editTransferNoteData || !editTransferNoteData.details || !editTransferNoteData.details.length) {
                return;
            }

            $('#productRows').html('');

            $.each(editTransferNoteData.details, function(_, item) {
                addProductRow();
                let row = $('#productRows tr.product-row').last();

                row.find('.product-select').val(item.product_id);
                row.data('available_quantity', item.available_quantity);
                row.data('unit_cost', item.unit_cost);
                row.find('.available-qty').html(decimal(item.available_quantity));

                loadVariations(item.product_id, row, function(row) {
                    row.find('.variation-select').val(item.product_variation_id);
                    row.find('.selected-unit-id').val(item.unit_id);
                    row.find('.selected-unit-name').html(item.unit_name);
                    row.find('.conversion-factor').val(item.conversion_factor || 1);
                    row.find('.transfer-qty').val(decimal(item.transfer_quantity));

                    loadConversions(item.product_variation_id, row);

                    if (item.product_variation_unit_conversion_id) {
                        setTimeout(function() {
                            row.find('.conversion-select').val(item.product_variation_unit_conversion_id);
                        }, 500);
                    }

                    calculateRow(row);
                });
            });
        }

        // ======================================================
        // FORM SUBMIT
        // ======================================================

        $('#transferNoteForm').on('submit', function(e) {
            if (!$('#source_warehouse_id').val() || !$('#destination_warehouse_id').val()) {
                e.preventDefault();
                errorMessage('Please select both source and destination warehouse.');
                return false;
            }

            if ($('#source_warehouse_id').val() === $('#destination_warehouse_id').val()) {
                e.preventDefault();
                errorMessage('Source and destination warehouse cannot be the same.');
                return false;
            }

            if ($('#productRows tr.product-row').length == 0) {
                e.preventDefault();
                errorMessage('Please add at least one product.');
                return false;
            }

            let hasQuantity = false;
            $('#productRows .transfer-qty').each(function() {
                if (decimal($(this).val()) > 0) {
                    hasQuantity = true;
                }
            });

            if (!hasQuantity) {
                e.preventDefault();
                errorMessage('Please enter a transfer quantity for at least one product.');
                return false;
            }
        });
    </script>
@endsection
