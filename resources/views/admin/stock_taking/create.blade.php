@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ isset($stock_taking) ? 'Update' : 'New' }} Stock Taking</h4>
        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($stock_taking) ? 'Update' : 'Create' }} Stock Taking</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/stock-taking') }}" method="POST" id="stockTakingForm">
                    @csrf
                    <input type="hidden" name="stock_taking_id" value="{{ $stock_taking->stock_taking_id ?? '' }}">
                    {{-- ================= HEADER ================= --}}
                    <div class="row">
                        @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3 mb-3">
                                <label>Business <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="business_id" id="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ old('business_id', $stock_taking->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
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
                            <select class="form-control select2" name="warehouse_id" id="warehouse_id"
                                {{ isset($stock_taking) ? 'disabled' : '' }}>
                                <option value="">--Select Warehouse--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}"
                                        {{ old('warehouse_id', $stock_taking->warehouse_id ?? '') == $item->warehouse_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if (isset($stock_taking))
                                <input type="hidden" name="warehouse_id" value="{{ $stock_taking->warehouse_id }}">
                            @endif
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Stock Taking No.</label>
                            <input type="text" class="form-control" name="stock_taking_no" readonly
                                value="{{ $stock_taking->stock_taking_no ?? ($stock_taking_no ?? 'Auto Generated') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Date</label>
                            <input type="text" class="form-control datepicker" name="stock_taking_date"
                                value="{{ old('stock_taking_date', isset($stock_taking) ? localDate($stock_taking->stock_taking_date) : localDate(date('Y-m-d'))) }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Reference</label>
                            <input type="text" class="form-control" name="reference"
                                value="{{ old('reference', $stock_taking->reference ?? '') }}">
                        </div>
                        <div class="col-md-9 mb-3">
                            <label>Notes</label>
                            <textarea class="form-control" rows="1" name="description">{{ old('description', $stock_taking->description ?? '') }}</textarea>
                        </div>
                    </div>
                    <hr>
                    {{-- ================= PRODUCT TABLE ================= --}}
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                Products
                            </h5>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="loadStockBtn"
                                    {{ isset($stock_taking) ? 'disabled' : '' }}>
                                    <i class="fa fa-download"></i> Load Warehouse Stock
                                </button>
                                <button type="button" class="btn btn-sm btn-primary" id="addProductBtn">
                                    <i class="fa fa-plus"></i> Add Product
                                </button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="productTable">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px;">Product</th>
                                        <th style="min-width:150px;">Variation</th>
                                        <th style="min-width:90px;">Unit</th>
                                        <th style="min-width:120px;">System Qty</th>
                                        <th style="min-width:130px;">Physical Qty</th>
                                        <th style="min-width:110px;">Difference</th>
                                        <th style="min-width:130px;">Diff. Value</th>
                                        <th style="min-width:180px;">Reason</th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="productRows">
                                    <tr id="emptyRow">
                                        <td colspan="9" class="text-center text-muted">
                                            Select a warehouse, then "Load Warehouse Stock" or "Add Product"
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
                                    <th>Total Difference Qty</th>
                                    <td>
                                        <input class="form-control" id="total_difference_quantity" readonly>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Total Difference Value</th>
                                    <td>
                                        <input class="form-control fw-bold" id="total_difference_value" readonly>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <button class="text-end btn btn-primary" id="submitBtn">
                                {{ isset($stock_taking) ? 'Update Stock Taking' : 'Save Stock Taking' }}
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
        var isEditMode = {{ isset($stock_taking) ? 'true' : 'false' }};
        var editStockTakingData = @json($stock_taking_details ?? null);
        var productsData = @json($products);
        var productIndex = 0;

        $(function() {
            if ($.fn.select2) {
                $('.select2').select2({
                    width: '100%'
                });
            }

            if (isEditMode) {
                loadStockTakingForEdit();
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
                    }
                },
                error: function() {
                    errorMessage('Unable to load products.');
                }
            });
        }

        $(document).on('change', '#warehouse_id', function() {
            resetProductRows();
        });

        function resetProductRows() {
            $('#productRows').html(`
        <tr id="emptyRow">
            <td colspan="9" class="text-center text-muted">
                Select a warehouse, then "Load Warehouse Stock" or "Add Product"
            </td>
        </tr>
    `);
            calculateGrandTotal();
        }

        // ======================================================
        // LOAD SYSTEM STOCK
        // ======================================================

        $('#loadStockBtn').on('click', function() {
            let warehouseId = $('#warehouse_id').val();
            if (!warehouseId) {
                errorMessage('Please select a warehouse first.');
                return;
            }
            loadSystemStock(warehouseId);
        });

        function loadSystemStock(warehouseId) {
            $.ajax({
                url: url_local + '/admin/stock-taking/system-stock/' + warehouseId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    $('#productRows').html(`
                <tr>
                    <td colspan="9" class="text-center">
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
                    $('#productRows').html('');
                    if (!response.Data.length) {
                        $('#productRows').html(`
                    <tr id="emptyRow">
                        <td colspan="9" class="text-center text-muted">
                            No stock found for this warehouse. Use "Add Product" to count a new item.
                        </td>
                    </tr>
                `);
                        return;
                    }
                    $.each(response.Data, function(_, line) {
                        addProductRow(line);
                    });
                    calculateGrandTotal();
                },
                error: function() {
                    errorMessage('Unable to load warehouse stock.');
                }
            });
        }

        // ======================================================
        // MANUAL ADD PRODUCT (for items not yet in stock)
        // ======================================================

        $('#addProductBtn').on('click', function() {
            if (!$('#warehouse_id').val()) {
                errorMessage('Please select a warehouse first.');
                return;
            }
            addManualProductRow();
        });

        function addManualProductRow() {
            $('#emptyRow').remove();
            const index = productIndex;
            let row = $(`
        <tr class="product-row">
            <td>
                <select name="products[${index}][product_id]" class="form-control manual-product-select">
                    <option value="">--Select Product--</option>
                </select>
            </td>
            <td>
                <select name="products[${index}][product_variation_id]" class="form-control manual-variation-select">
                    <option value="">--Select Variation--</option>
                </select>
            </td>
            <td>
                <input type="hidden" class="selected-unit-id" name="products[${index}][unit_id]" value="">
                <span class="selected-unit-name">-</span>
            </td>
            <td class="system-qty">${decimal(0)}</td>
            <td>
                <input type="text" class="form-control physical-qty" name="products[${index}][physical_quantity]" value="0">
            </td>
            <td class="difference-qty">${decimal(0)}</td>
            <td class="difference-value">${decimal(0)}</td>
            <td>
                <input type="text" class="form-control reason" name="products[${index}][reason]" value="">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-row">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `);
            row.data('system_quantity', 0);
            row.data('unit_cost', 0);
            $('#productRows').append(row);
            loadManualProductDropdown(row);
            productIndex++;
        }

        function loadManualProductDropdown(row) {
            let html = `<option value="">--Select Product--</option>`;
            $.each(productsData, function(_, product) {
                html += `<option value="${product.product_id}">${product.name}</option>`;
            });
            row.find('.manual-product-select').html(html);
        }

        $(document).on('change', '.manual-product-select', function() {
            let row = $(this).closest('tr');
            let productId = $(this).val();

            row.find('.manual-variation-select').html('<option value="">--Select Variation--</option>');
            row.find('.selected-unit-id').val('');
            row.find('.selected-unit-name').html('-');
            row.data('system_quantity', 0);
            row.data('unit_cost', 0);

            if (!productId) {
                return;
            }

            $.ajax({
                url: url_local + '/admin/product/variation-by-product/' + productId,
                type: 'GET',
                dataType: 'json',
                beforeSend: function() {
                    row.find('.manual-variation-select').html('<option>Loading...</option>');
                },
                success: function(response) {
                    let html = '<option value="">--Select Variation--</option>';
                    if (response.Success && response.Data.length) {
                        $.each(response.Data, function(_, variation) {
                            html += `
                    <option
                        value="${variation.product_variation_id}"
                        data-unit-id="${variation.unit?.unit_id ?? variation.base_unit_id ?? ''}"
                        data-unit-name="${variation.unit?.name ?? ''}"
                    >
                        ${variation.name}
                    </option>
                `;
                        });
                    }
                    row.find('.manual-variation-select').html(html);
                },
                error: function() {
                    errorMessage('Unable to load variations.');
                }
            });
        });

        $(document).on('change', '.manual-variation-select', function() {
            let row = $(this).closest('tr');
            let option = $(this).find(':selected');

            row.find('.selected-unit-id').val(option.data('unit-id') || '');
            row.find('.selected-unit-name').html(option.data('unit-name') || '-');
            row.data('system_quantity', 0);
            row.data('unit_cost', 0);

            calculateRow(row);
        });

        // ======================================================
        // SYSTEM-STOCK ROW TEMPLATE
        // ======================================================

        function addProductRow(line, physical_quantity) {
            const isSerialTracked = !!line.track_serial_number;
            // Serial-tracked lines are reconciled unit-by-unit via the
            // Serial Number screens, not a blind quantity override here -
            // the physical count always mirrors system stock and can't be
            // edited (server also enforces this, see StockTakingService::save()).
            physical_quantity = isSerialTracked ? (line.system_quantity ?? 0) : (physical_quantity ?? line.system_quantity ?? 0);
            const index = productIndex;

            let row = $(`
        <tr class="product-row">
            <td>
                <input type="hidden" name="products[${index}][product_id]" value="${line.product_id}">
                ${line.product_name}
            </td>
            <td>
                <input type="hidden" name="products[${index}][product_variation_id]" value="${line.product_variation_id}">
                ${line.variation_name}
            </td>
            <td>
                <input type="hidden" name="products[${index}][unit_id]" value="${line.unit_id ?? ''}">
                ${line.unit_name ?? '-'}
            </td>
            <td class="system-qty">${decimal(line.system_quantity)}</td>
            <td>
                <input type="text" class="form-control physical-qty" name="products[${index}][physical_quantity]"
                    value="${decimal(physical_quantity)}" ${isSerialTracked ? 'readonly' : ''}>
                ${isSerialTracked ? '<small class="text-muted d-block">Serial-tracked - reconcile via Serial Number screens</small>' : ''}
            </td>
            <td class="difference-qty">${decimal(0)}</td>
            <td class="difference-value">${decimal(0)}</td>
            <td>
                <input type="text" class="form-control reason" name="products[${index}][reason]" value="">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger remove-row">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        </tr>
    `);

            row.data('system_quantity', decimal(line.system_quantity));
            row.data('unit_cost', decimal(line.unit_cost));

            $('#productRows').append(row);
            productIndex++;
            calculateRow(row);
        }

        $(document).on('click', '.remove-row', function() {
            $(this).closest('tr').remove();
            if ($('#productRows tr.product-row').length == 0) {
                resetProductRows();
            }
            calculateGrandTotal();
        });

        // ======================================================
        // ROW CALCULATION
        // ======================================================

        $(document).on('keyup change', '.physical-qty', function() {
            calculateRow($(this).closest('tr'));
        });

        function calculateRow(row) {
            let systemQty = decimal(row.data('system_quantity'));
            let unitCost = decimal(row.data('unit_cost'));
            let physicalQty = decimal(row.find('.physical-qty').val());

            let difference = physicalQty - systemQty;
            let differenceValue = difference * unitCost;

            row.find('.difference-qty').html(decimal(difference));
            row.find('.difference-value').html(decimal(differenceValue));
            row.data('difference_quantity', difference);
            row.data('difference_value', differenceValue);

            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let total_difference_quantity = 0;
            let total_difference_value = 0;

            $('#productRows tr.product-row').each(function() {
                let row = $(this);
                total_difference_quantity += decimal(row.data('difference_quantity'));
                total_difference_value += decimal(row.data('difference_value'));
            });

            $('#total_difference_quantity').val(decimal(total_difference_quantity));
            $('#total_difference_value').val(decimal(total_difference_value));
        }

        // ======================================================
        // EDIT MODE
        // ======================================================

        function loadStockTakingForEdit() {
            if (!editStockTakingData || !editStockTakingData.details || !editStockTakingData.details.length) {
                return;
            }

            $('#productRows').html('');

            $.each(editStockTakingData.details, function(_, item) {
                addProductRow({
                    product_id: item.product_id,
                    product_name: item.product_name,
                    product_variation_id: item.product_variation_id,
                    variation_name: item.variation_name,
                    unit_id: item.unit_id,
                    unit_name: item.unit_name,
                    system_quantity: item.system_quantity,
                    unit_cost: item.unit_cost,
                    track_serial_number: item.track_serial_number,
                }, item.physical_quantity);

                $('#productRows tr.product-row').last().find('.reason').val(item.reason ?? '');
            });

            calculateGrandTotal();
        }

        // ======================================================
        // FORM SUBMIT
        // ======================================================

        $('#stockTakingForm').on('submit', function(e) {
            if ($('#productRows tr.product-row').length == 0) {
                e.preventDefault();
                errorMessage('Please add at least one product to count.');
                return false;
            }
        });
    </script>
@endsection
