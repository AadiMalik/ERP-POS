@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($opening_stock) ? 'Update' : 'New' }} Opening Stock</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($opening_stock) ? 'Update' : 'Create' }} Opening Stock</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/opening-stock') }}" method="POST" id="openingStockForm">
                    @csrf
                    <input type="hidden" name="opening_stock_id" value="{{ $opening_stock->opening_stock_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $opening_stock->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                            {{ $item->code }} - {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3 mb-3">
                            <label>
                                Warehouse<span class="text-danger">*</span>
                            </label>
                            <select class="form-control select2" name="warehouse_id" id="warehouse_id">
                                <option value="">--Select Warehouse--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}"
                                        {{ old('warehouse_id', $opening_stock->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Opening Stock No.</label>
                            <input type="text" class="form-control" name="opening_stock_no" readonly
                                value="{{ $opening_stock->opening_stock_no ?? ($opening_stock_no ?? 'Auto Generated') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Date</label>
                            <input type="text" class="form-control datepicker" name="opening_stock_date"
                                value="{{ old('opening_stock_date', isset($opening_stock) ? localDate($opening_stock->opening_stock_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Reference</label>
                            <input type="text" class="form-control" name="reference"
                                value="{{ old('reference', $opening_stock->reference ?? '') }}">
                        </div>
                        <div class="col-md-9 mb-3">
                            <label>Notes</label>
                            <textarea class="form-control" rows="1" name="description">{{ old('description', $opening_stock->description ?? '') }}</textarea>
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
                                        <th style="min-width:110px;">Quantity</th>
                                        <th style="min-width:120px;">Unit Cost</th>
                                        <th style="min-width:130px;">Batch No.</th>
                                        <th style="min-width:150px;">Expiry Date</th>
                                        <th style="min-width:130px">Total Value</th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    <tr id="emptyRow">
                                        <td colspan="10" class="text-center text-muted">
                                            Click "Add Product" to begin
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
                                {{ isset($opening_stock) ? 'Update Opening Stock' : 'Save Opening Stock' }}
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
        var isEditMode = {{ isset($opening_stock) ? 'true' : 'false' }};
        var editOpeningStockData = @json($opening_stock_details ?? null);
        var productsData = @json($products);
        var productIndex = 0;

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            if (isEditMode) {
                loadOpeningStockForEdit();
            } else {
                addProductRow();
            }
        });

        // ======================================================
        // BUSINESS CHANGE (Super Admin only - reload warehouses/products
        // scoped to the selected business, since the server can only
        // preload data for the logged-in user's own business)
        // ======================================================

        $(document).on('change', '#business_id', function() {
            let businessId = $(this).val();

            $('#warehouse_id').html('<option value="">--Select Warehouse--</option>');
            productsData = [];
            refreshProductDropdowns();

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
                    $('#warehouse_id').prop('disabled', true).html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select Warehouse--</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, warehouse) {
                            html += `<option value="${warehouse.warehouse_id}">${warehouse.name}</option>`;
                        });
                    }
                    $('#warehouse_id').html(html).prop('disabled', false);
                },
                error: function() {
                    $('#warehouse_id').html('<option value="">--Select Warehouse--</option>').prop('disabled', false);
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
        // ROW TEMPLATE
        // ======================================================

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
        <td>
            <input type="text" class="form-control qty" name="products[${index}][quantity]" value="0">
        </td>
        <td>
            <input type="text" class="form-control unit-cost" name="products[${index}][unit_cost]" value="0">
        </td>
        <td class="batch-cell">
            <input type="text" class="form-control batch-no" name="products[${index}][batch_no]" value="" placeholder="Batch No." style="display:none;">
            <span class="batch-no-na text-muted">N/A</span>
        </td>
        <td class="expiry-cell">
            <input type="text" class="form-control datepicker expiry-date" name="products[${index}][expiry_date]" value="" placeholder="Expiry Date" style="display:none;">
            <span class="expiry-date-na text-muted">N/A</span>
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

        $('#addProductBtn').on('click', function() {
            addProductRow();
        });

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            if ($('#productRows tr.product-row').length == 0) {
                $('#productRows').html(`
            <tr id="emptyRow">
                <td colspan="10" class="text-center text-muted">
                    Click "Add Product" to begin
                </td>
            </tr>
        `);
            }
            calculateGrandTotal();
        });

        // ======================================================
        // PRODUCT DROPDOWN
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
            row.find('.unit-cost').val(0);
            setBatchExpiryState(row, false, false);
        }

        // The batch/expiry <td>s always stay in the row so every row keeps
        // the same number of cells as the header (hiding a <td> itself would
        // shift the remaining cells in that row into the wrong columns).
        // Only the input inside is toggled, with a muted "N/A" shown instead.
        function setBatchExpiryState(row, showBatch, showExpiry) {
            if (showBatch) {
                row.find('.batch-no').show();
                row.find('.batch-no-na').hide();
            } else {
                row.find('.batch-no').val('').hide();
                row.find('.batch-no-na').show();
            }

            if (showExpiry) {
                row.find('.expiry-date').show();
                row.find('.expiry-date-na').hide();
                let expiryInput = row.find('.expiry-date').get(0);
                if (expiryInput && typeof flatpickr === 'function' && !expiryInput._flatpickr) {
                    flatpickr(expiryInput, {
                        dateFormat: "{{ session('business_setting.date_format', 'd-m-Y') }}",
                        allowInput: true
                    });
                }
            } else {
                row.find('.expiry-date').val('').hide();
                row.find('.expiry-date-na').show();
            }
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
                        data-price="${decimal(variation.purchase_price ?? 0)}"
                        data-track-batch="${variation.track_batch ? 1 : 0}"
                        data-track-expiry="${variation.track_expiry ? 1 : 0}"
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
            row.find('.unit-cost').val(decimal(option.data('price')));
            row.find('.conversion-factor').val(1);

            setBatchExpiryState(row, option.data('track-batch') == 1, option.data('track-expiry') == 1);

            row.find('.conversion-select').html(`<option value="">Loading...</option>`);

            if (!variationId) {
                calculateRow(row);
                return;
            }

            loadConversions(variationId, row);
            calculateRow(row);
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
        // ROW CALCULATION
        // ======================================================

        $(document).on('keyup change', '.qty,.unit-cost', function() {
            calculateRow($(this).closest('tr'));
        });

        function calculateRow(row) {
            let qty = decimal(row.find('.qty').val());
            let unitCost = decimal(row.find('.unit-cost').val());
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
                let qty = decimal(row.find('.qty').val());
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

        function loadOpeningStockForEdit() {
            if (!editOpeningStockData || !editOpeningStockData.details || !editOpeningStockData.details.length) {
                addProductRow();
                return;
            }

            $('#productRows').html('');

            $.each(editOpeningStockData.details, function(_, item) {
                addProductRow();
                let row = $('#productRows tr.product-row').last();

                row.find('.product-select').val(item.product_id);

                loadVariations(item.product_id, row, function(row) {
                    row.find('.variation-select').val(item.product_variation_id);
                    row.find('.selected-unit-id').val(item.unit_id);
                    row.find('.selected-unit-name').html(item.unit_name);
                    row.find('.unit-cost').val(decimal(item.unit_cost));
                    row.find('.qty').val(decimal(item.quantity));
                    row.find('.conversion-factor').val(item.conversion_factor || 1);

                    setBatchExpiryState(row, item.track_batch == 1, item.track_expiry == 1);

                    if (item.track_batch == 1) {
                        row.find('.batch-no').val(item.batch_no ?? '');
                    }
                    if (item.track_expiry == 1) {
                        row.find('.expiry-date').val(item.expiry_date ?? '');
                    }

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

        $('#openingStockForm').on('submit', function(e) {
            if ($('#productRows tr.product-row').length == 0) {
                e.preventDefault();
                errorMessage('Please add at least one product.');
                return false;
            }

            let hasQuantity = false;
            $('#productRows .qty').each(function() {
                if (decimal($(this).val()) > 0) {
                    hasQuantity = true;
                }
            });

            if (!hasQuantity) {
                e.preventDefault();
                errorMessage('Please enter a quantity for at least one product.');
                return false;
            }
        });
    </script>
@endsection
