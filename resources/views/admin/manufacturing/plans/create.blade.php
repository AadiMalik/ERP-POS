@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ isset($plan) ? 'Edit' : 'New' }} Manufacturing Plan</h4>
    <form id="planForm" method="POST" action="{{ url('admin/manufacturing-plan/store') }}">
        @csrf
        <input type="hidden" name="manufacturing_plan_id" value="{{ $plan->manufacturing_plan_id ?? '' }}">
        <input type="hidden" name="product_recipe_id" id="product_recipe_id" value="{{ $plan->product_recipe_id ?? '' }}">
        <div class="card mb-4">
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" id="branch_id" class="form-select" required>
                        <option value="">--Select--</option>
                        @foreach ($branches as $item)
                        <option value="{{ $item->branch_id }}" {{ ($plan->branch_id ?? null) == $item->branch_id ? 'selected' : '' }}>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Finished Product <span class="text-danger">*</span></label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">--Select--</option>
                        @foreach ($products as $item)
                        <option value="{{ $item->product_id }}" {{ ($plan->product_id ?? null) == $item->product_id ? 'selected' : '' }}>{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Finished Product Variation <span class="text-danger">*</span></label>
                    <select name="product_variation_id" id="product_variation_id" class="form-select" required>
                        @if (isset($plan))
                        <option value="{{ $plan->product_variation_id }}" selected>{{ $plan->productVariation->name ?? '' }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Base Unit</label>
                    <input type="text" id="baseUnitDisplay" class="form-control" readonly value="{{ $plan->productVariation->unit->name ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Planned Quantity <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" name="planned_quantity" id="planned_quantity" class="form-control" value="{{ $plan->planned_quantity ?? '' }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Plan Date</label>
                    <input type="date" name="plan_date" class="form-control" value="{{ $plan->plan_date ?? date('Y-m-d') }}">
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Recipe - Raw Materials Required</h6>
            </div>
            <div class="card-body">
                <div id="noRecipeMessage" class="alert alert-warning" style="display:none;">
                    No active recipe found for this product/variation. <a href="{{ url('admin/recipe/create') }}" target="_blank">Create one first</a>.
                </div>
                <div id="recipeNameDisplay" class="mb-2 text-muted"></div>
                <table class="table table-bordered" id="recipeItemsTable" style="display:none;">
                    <thead>
                        <tr>
                            <th>Raw Material</th>
                            <th>Unit</th>
                            <th>Qty per Unit</th>
                            <th>Warehouse</th>
                            <th>Required Quantity</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="text-end mb-4">
            <a href="{{ url('admin/manufacturing-plan') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" id="savePlanBtn" class="btn btn-primary" disabled>Save Plan</button>
        </div>
    </form>
</div>
@endsection
@section('js')
<script>
    let currentRecipe = null;

    function fetchVariations(productId, selectedId) {
        if (!productId) { $('#product_variation_id').html(''); return; }
        ajaxRequest({ url: url_local + '/admin/product/variation-by-product/' + productId, data: {} })
            .then((response) => {
                let options = '';
                (response.Data || []).forEach(v => {
                    options += `<option value="${v.product_variation_id}" data-unit-name="${v.unit ? v.unit.name : ''}" ${selectedId == v.product_variation_id ? 'selected' : ''}>${v.name}</option>`;
                });
                $('#product_variation_id').html(options).trigger('change');
            });
    }

    function fetchRecipe(variationId) {
        currentRecipe = null;
        $('#product_recipe_id').val('');
        $('#noRecipeMessage').hide();
        $('#recipeItemsTable').hide();
        $('#recipeNameDisplay').text('');
        document.getElementById('savePlanBtn').disabled = true;

        if (!variationId) return;

        ajaxRequest({ url: url_local + '/admin/manufacturing-plan/recipe-for-variation/' + variationId, data: {} })
            .then((response) => {
                currentRecipe = response.Data;
                $('#product_recipe_id').val(currentRecipe.product_recipe_id);
                $('#recipeNameDisplay').text('');
                renderRecipeTable();
                $('#recipeItemsTable').show();
                document.getElementById('savePlanBtn').disabled = false;
            })
            .catch(() => {
                $('#noRecipeMessage').show();
            });
    }

    function renderRecipeTable() {
        if (!currentRecipe) return;
        const plannedQty = parseFloat($('#planned_quantity').val()) || 0;
        let html = '';
        (currentRecipe.items || []).forEach(item => {
            // Recipe quantities are always per one finished unit, in the raw
            // material's own base unit - a straight multiply.
            const required = parseFloat(item.quantity) * plannedQty;
            html += `<tr>
                <td>${item.raw_material_variation ? item.raw_material_variation.name : ''}</td>
                <td>${item.unit ? item.unit.name : ''}</td>
                <td>${item.quantity}</td>
                <td>${item.warehouse ? item.warehouse.name : ''}</td>
                <td><strong>${required.toFixed(4)}</strong></td>
            </tr>`;
        });
        $('#recipeItemsTable tbody').html(html);
    }

    $('#product_id').change(function() { fetchVariations($(this).val(), null); });
    $('#product_variation_id').on('change', function() {
        $('#baseUnitDisplay').val($(this).find(':selected').data('unit-name') || '');
        fetchRecipe($(this).val());
    });
    $('#planned_quantity').on('keyup change', function() { renderRecipeTable(); });

    $(document).ready(function() {
        $('#product_variation_id').select2();
        @isset($plan)
        fetchVariations('{{ $plan->product_id }}', '{{ $plan->product_variation_id }}');
        @endisset
    });
</script>
@endsection
