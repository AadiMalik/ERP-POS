@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Recipe / BOM</h4>
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Product <span class="text-danger">*</span></label>
                    <select id="product_id" class="form-select">
                        <option value="">--Select--</option>
                        @foreach ($products as $item)
                        <option value="{{ $item->product_id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Variation <span class="text-danger">*</span></label>
                    <select id="product_variation_id" class="form-select"></select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Base Unit</label>
                    <input type="text" id="finishedBaseUnitDisplay" class="form-control" readonly>
                    <small class="text-muted">Stock for this product is always kept in this unit.</small>
                </div>
            </div>
            <div id="recipeStatusMessage" class="alert alert-info mt-3" style="display:none;"></div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Raw Material Components</h6>
            <button type="button" class="btn btn-sm btn-primary" id="addComponentBtn" onclick="openLineModal()" disabled><i class="fa fa-plus"></i> Add Raw Material</button>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="componentsTable">
                <thead><tr><th>Raw Material</th><th>Qty</th><th>Unit</th><th>Consume From (Warehouse)</th><th></th></tr></thead>
                <tbody></tbody>
            </table>
            <small class="text-muted" id="selectFinishedFirstHint">Select the product/variation above first.</small>
        </div>
    </div>
</div>

<!-- Line item modal -->
<div class="modal fade" id="lineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Raw Material</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Product <span class="text-danger">*</span></label>
                    <select id="lineProduct" class="form-select">
                        <option value="">--Select--</option>
                        @foreach ($products as $item)
                        <option value="{{ $item->product_id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">The product being manufactured is not shown here - a recipe cannot consume itself.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Variation <span class="text-danger">*</span></label>
                    <select id="lineVariation" class="form-select"></select>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                        <input type="number" step="0.0001" id="lineQuantity" class="form-control">
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Base Unit</label>
                        <input type="text" id="lineUnitDisplay" class="form-control" readonly>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Consume From (Warehouse) <span class="text-danger">*</span></label>
                    <select id="lineWarehouse" class="form-select">
                        <option value="">--Select--</option>
                        @foreach ($warehouses as $item)
                        <option value="{{ $item->warehouse_id }}">{{ $item->name }}{{ $item->branch ? ' (' . $item->branch->name . ')' : ' (Shared)' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveLineBtn" onclick="saveLineRow()">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
    let lineModal;
    let currentItems = [];

    function fetchVariations(productId, selectEl, cb) {
        if (!productId) { selectEl.html(''); if (cb) cb(); return; }
        ajaxRequest({ url: url_local + '/admin/product/variation-by-product/' + productId, data: {} })
            .then((response) => {
                let options = '';
                (response.Data || []).forEach(v => {
                    options += `<option value="${v.product_variation_id}" data-base-unit-name="${v.unit ? v.unit.name : ''}">${v.name}</option>`;
                });
                selectEl.html(options);
                if (cb) cb();
            });
    }

    function loadExistingRecipe(variationId) {
        renderComponents([]);
        $('#recipeStatusMessage').hide();

        ajaxRequest({ url: url_local + '/admin/recipe/for-variation/' + variationId, data: {} })
            .then((response) => {
                renderComponents(response.Data.items || []);
                $('#recipeStatusMessage').removeClass('alert-warning').addClass('alert-info').text('Editing the existing recipe for this product.').show();
            })
            .catch(() => {
                renderComponents([]);
                $('#recipeStatusMessage').removeClass('alert-info').addClass('alert-warning').text('No recipe found yet for this product - add raw materials below to create one.').show();
            });
    }

    $('#product_id').change(function() {
        fetchVariations($(this).val(), $('#product_variation_id'), function() {
            $('#product_variation_id').trigger('change.select2').trigger('change');
        });
        document.getElementById('addComponentBtn').disabled = true;
    });
    $('#product_variation_id').on('change', function() {
        const unitName = $(this).find(':selected').data('base-unit-name');
        $('#finishedBaseUnitDisplay').val(unitName || '');
        const variationId = $(this).val();
        document.getElementById('addComponentBtn').disabled = !variationId;
        if (variationId) loadExistingRecipe(variationId);
    });

    function openLineModal() {
        const finishedProductId = $('#product_id').val();
        $('#lineProduct option').show();
        $('#lineProduct option[value="' + finishedProductId + '"]').hide();

        $('#lineProduct').val('').trigger('change.select2');
        $('#lineVariation').html('');
        $('#lineUnitDisplay').val('');
        $('#lineWarehouse').val('').trigger('change.select2');
        $('#lineQuantity').val('');
        lineModal.show();
    }

    $('#lineProduct').change(function() {
        fetchVariations($(this).val(), $('#lineVariation'), function() {
            $('#lineVariation').trigger('change');
        });
    });
    $('#lineVariation').on('change', function() {
        $('#lineUnitDisplay').val($(this).find(':selected').data('base-unit-name') || '');
    });

    function saveLineRow() {
        const productId = $('#lineProduct').val();
        const variationId = $('#lineVariation').val();
        const qty = parseFloat($('#lineQuantity').val()) || 0;
        const warehouseId = $('#lineWarehouse').val();

        if (!productId || !variationId || qty <= 0) {
            errorMessage('Product, variation and a positive quantity are required.');
            return;
        }
        if (!warehouseId) {
            errorMessage('Select the warehouse this raw material is consumed from.');
            return;
        }
        if (currentItems.some(item => item.raw_material_product_variation_id === variationId)) {
            errorMessage('This raw material is already added to the recipe.');
            return;
        }

        document.getElementById('saveLineBtn').disabled = true;
        ajaxRequest({
            url: url_local + '/admin/recipe/item',
            method: 'POST',
            data: {
                product_id: $('#product_id').val(),
                product_variation_id: $('#product_variation_id').val(),
                raw_material_product_id: productId,
                raw_material_product_variation_id: variationId,
                quantity: qty,
                warehouse_id: warehouseId,
            },
        }).then((response) => {
            document.getElementById('saveLineBtn').disabled = false;
            lineModal.hide();
            successMessage(response.Message);
            loadExistingRecipe($('#product_variation_id').val());
        }).catch((err) => {
            document.getElementById('saveLineBtn').disabled = false;
            errorMessage(err.Message);
        });
    }

    function renderComponents(items) {
        currentItems = items;
        $('#selectFinishedFirstHint').toggle(!$('#product_variation_id').val());
        let html = '';
        items.forEach((item) => {
            const productName = item.raw_material_product ? item.raw_material_product.name : '';
            const variationName = item.raw_material_variation ? item.raw_material_variation.name : '';
            const unitName = item.unit ? item.unit.name : '';
            const warehouseName = item.warehouse ? (item.warehouse.name + (item.warehouse.branch ? ' (' + item.warehouse.branch.name + ')' : ' (Shared)')) : '';
            html += `<tr data-item-id="${item.product_recipe_item_id}"><td>${productName} - ${variationName}</td><td>${item.quantity}</td><td>${unitName}</td><td>${warehouseName}</td><td><button type="button" class="btn btn-sm btn-danger" onclick="removeComponent('${item.product_recipe_item_id}')"><i class="fa fa-trash"></i></button></td></tr>`;
        });
        $('#componentsTable tbody').html(html);
    }

    function removeComponent(itemId) {
        Swal.fire({
            title: 'Remove this raw material from the recipe?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Remove'
        }).then((result) => {
            if (!result.isConfirmed) return;
            ajaxRequest({ url: url_local + '/admin/recipe/item/' + itemId, method: 'DELETE', data: {} })
                .then((response) => {
                    successMessage(response.Message);
                    currentItems = currentItems.filter(item => item.product_recipe_item_id !== itemId);
                    $('#componentsTable tbody tr[data-item-id="' + itemId + '"]').remove();
                })
                .catch((err) => errorMessage(err.Message));
        });
    }

    $(document).ready(function() {
        lineModal = new bootstrap.Modal(document.getElementById('lineModal'));
        $('#product_id, #product_variation_id').select2();
        $('#lineProduct, #lineVariation, #lineWarehouse').select2({ dropdownParent: $('#lineModal') });
    });
</script>
@endsection
