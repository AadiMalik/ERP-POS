@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
<style>

    .feature-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        min-height: 40px;
        padding: 0.25rem;
    }

    .feature-badge {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: 0.2s;
    }

    .feature-badge:hover {
        background: #e9ecef;
    }

    .feature-badge .feature-description {
        color: #6c757d;
    }

    .feature-badge .remove-feature-btn {
        background: none;
        border: none;
        color: #6c757d;
        padding: 0 0.2rem;
        font-size: 1rem;
        line-height: 1;
        cursor: pointer;
        transition: 0.2s;
    }

    .feature-badge .remove-feature-btn:hover {
        color: #dc3545;
    }

    .attribute-container {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        min-height: 30px;
        padding: 0.25rem 0;
    }

    .attribute-badge {
        background: #fff;
        border: 1px solid #dee2e6;
        padding: 0.15rem 0.6rem;
        border-radius: 1rem;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }

    .attribute-badge .remove-attr-btn {
        background: none;
        border: none;
        padding: 0 0.2rem;
        font-size: 0.8rem;
        line-height: 1;
        color: #6c757d;
        cursor: pointer;
    }

    .attribute-badge .remove-attr-btn:hover {
        color: #dc3545;
    }

    .variation-table-wrapper {
        overflow-x: auto;
    }

    .variation-table-wrapper table {
        font-size: 0.85rem;
    }

    .variation-table-wrapper table .btn-sm {
        padding: 0.15rem 0.4rem;
        font-size: 0.75rem;
    }

    .preview-placeholder {
        color: #6c757d;
        font-size: 0.9rem;
        padding: 1rem 0;
        text-align: center;
        width: 100%;
    }

    .modal-body .variation-form .form-label {
        font-weight: 600;
        font-size: 0.85rem;
    }

    .sticky-footer {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 0.75rem 1.5rem;
        border-top: 1px solid #dee2e6;
        border-radius: 0 0 1rem 1rem;
        z-index: 10;
    }

    .features-section-hidden {
        display: none !important;
    }

</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ isset($product) ? 'Update' : 'Create' }} Product</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($product) ? 'Update' : 'New' }} Product</h5>
        </div>

        <form id="productForm"
            action="{{url('admin/product') }}"
            method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->product_id ?? '' }}">

            <div class="card-body">
                <div class="row g-4">
                    <div class="col-lg-12">
                        <!-- BASIC INFO -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fa fa-info-circle me-1"></i>Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" id="productName"
                                            value="{{ $product->name ?? '' }}" placeholder="e.g. Classic Hoodie"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Slug</label>
                                        <input type="text" class="form-control" name="slug" id="productSlug"
                                            value="{{ $product->slug ?? '' }}" placeholder="auto-generated" readonly>
                                    </div>
                                    @if (getRoleName() == RoleNames::SUPERADMIN)
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Business <span
                                                class="text-danger">*</span></label>
                                        <select class="form-select" name="business_id" id="business_id" required>
                                            <option value="">--Select Business--</option>
                                            @foreach ($businesses as $item)
                                            <option value="{{ $item->business_id }}"
                                                {{ isset($product) && $product->business_id == $item->business_id ? 'selected' : '' }}>
                                                {{ $item->code ?? '' }} {{ $item->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Category <span class="text-danger">*</span></label>
                                        <select class="form-select" name="category_id" id="category_id" required>
                                            <option value="">--Select Category--</option>
                                            @if (getRoleName() != RoleNames::SUPERADMIN)
                                            @foreach ($categories as $item)
                                            <option value="{{ $item->category_id }}"
                                                {{ isset($product) && $product->category_id == $item->category_id ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Sub Category</label>
                                        <select class="form-select" name="sub_category_id" id="sub_category_id">
                                            <option value="">--Select Sub Category--</option>
                                            
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Brand <span class="text-danger">*</span></label>
                                        <select class="form-select" name="brand_id" id="brand_id" required>
                                            <option value="">--Select Brand--</option>
                                            @if (getRoleName() != RoleNames::SUPERADMIN)
                                            @foreach ($brands as $item)
                                            <option value="{{ $item->brand_id }}"
                                                {{ isset($product) && $product->brand_id == $item->brand_id ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                            @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Type</label>
                                        <select class="form-select" name="type" id="productType">
                                            <option value="single"
                                                {{ isset($product) && $product->type == 'single' ? 'selected' : '' }}>
                                                Single</option>
                                            <option value="variable"
                                                {{ isset($product) && $product->type == 'variable' ? 'selected' : '' }}>
                                                Variable</option>
                                            <option value="service"
                                                {{ isset($product) && $product->type == 'service' ? 'selected' : '' }}>
                                                Service</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Usage type</label>
                                        <select class="form-select" name="usage_type">
                                            <option value="saleable"
                                                {{ isset($product) && $product->usage_type == 'saleable' ? 'selected' : '' }}>
                                                Saleable</option>
                                            <option value="consumable"
                                                {{ isset($product) && $product->usage_type == 'consumable' ? 'selected' : '' }}>
                                                Consumable</option>
                                            <option value="asset"
                                                {{ isset($product) && $product->usage_type == 'asset' ? 'selected' : '' }}>
                                                Asset</option>
                                            <option value="service"
                                                {{ isset($product) && $product->usage_type == 'service' ? 'selected' : '' }}>
                                                Service</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="d-flex flex-wrap gap-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_track_stock"
                                                    id="trackStock"
                                                    {{ isset($product) && $product->is_track_stock ? 'checked' : '' }}>
                                                <label class="form-check-label" for="trackStock">Track stock</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_pos_visible"
                                                    id="posVisible"
                                                    {{ isset($product) && $product->is_pos_visible ? 'checked' : '' }}>
                                                <label class="form-check-label" for="posVisible">POS visible</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox"
                                                    name="is_website_visible" id="webVisible"
                                                    {{ isset($product) && $product->is_website_visible ? 'checked' : '' }}>
                                                <label class="form-check-label" for="webVisible">Website</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_app_visible"
                                                    id="appVisible"
                                                    {{ isset($product) && $product->is_app_visible ? 'checked' : '' }}>
                                                <label class="form-check-label" for="appVisible">App</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="is_featured"
                                                    id="featured"
                                                    {{ isset($product) && $product->is_featured ? 'checked' : '' }}>
                                                <label class="form-check-label" for="featured">Featured</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- VARIATIONS -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                <span><i class="fa fa-tags me-1"></i>Variations</span>
                                <div>
                                    <span class="badge bg-secondary me-2" id="variationCount">0</span>
                                    <button type="button" class="btn btn-sm btn-primary" id="openVariationModalBtn">
                                        <i class="fa fa-plus"></i> Add Variation
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="variation-table-wrapper">
                                    <table class="table table-bordered table-hover" id="variationTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>SKU</th>
                                                <th>Sale Price</th>
                                                <th>Attributes</th>
                                                <th style="width: 100px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="variationTableBody">
                                            <tr id="noVariationRow">
                                                <td colspan="5" class="text-center text-muted py-3">
                                                    <i class="fa fa-plus-circle me-1"></i> Click "Add Variation" to get
                                                    started
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <small class="text-muted"><i class="fa fa-info-circle me-1"></i> Each variation can
                                    have multiple attributes</small>
                            </div>
                        </div>

                        <!-- DESCRIPTIONS -->
                        <div class="card mb-4">
                            <div class="card-header bg-light"><i class="fa fa-align-left me-1"></i>Descriptions</div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="fw-semibold">Short description</label>
                                        <input type="text" class="form-control" name="short_description"
                                            value="{{ $product->short_description ?? '' }}"
                                            placeholder="Brief summary">
                                    </div>
                                    <div class="col-12">
                                        <label class="fw-semibold">Full description</label>
                                        <textarea class="form-control" name="description" rows="3" placeholder="Detailed description">{{ $product->description ?? '' }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FEATURES -->
                        <div class="card mb-4" id="featuresCard">
                            <div class="card-header bg-light">
                                <i class="fa fa-list me-1"></i>Features
                                <span class="badge bg-secondary ms-2" id="featureCount">0</span>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" id="featureNameInput"
                                            placeholder="Feature name e.g. Material">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" id="featureDescriptionInput"
                                            placeholder="Value e.g. Cotton 100%">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary w-100" id="addFeatureBtn">
                                            <i class="fa fa-plus"></i> Add
                                        </button>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="feature-list" id="featureList"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="card-footer border-top sticky-footer">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="history.back()">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4" id="saveProductBtn">
                        <i class="fa fa-save me-1"></i> {{ isset($product) ? 'Update' : 'Save' }} Product
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- VARIATION MODAL -->
<div class="modal fade" id="variationModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="variationModalTitle">
                    <i class="fa fa-plus-circle me-1"></i> Add Variation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="variationForm" class="variation-form">
                    <input type="hidden" id="editVariationIndex" value="">
                    <input type="hidden" id="editVariationId" value="">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Variation Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalVariationName"
                                placeholder="e.g. Small" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="modalVariationSku" placeholder="SKU001"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" id="modalVariationBarcode"
                                placeholder="1234567890">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit <span class="text-danger">*</span></label>
                            <select class="form-select" id="modalVariationUnit" required>
                                <option value="">--Select Unit--</option>
                                @foreach ($units as $unit)
                                <option value="{{ $unit->unit_id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Purchase Price</label>
                            <input type="number" step="0.01" class="form-control"
                                id="modalVariationPurchasePrice" value="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sale Price <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="modalVariationSalePrice"
                                value="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Minimum Stock</label>
                            <input type="number" step="1" class="form-control" id="modalVariationMinStock"
                                value="0">
                        </div>
                        <div class="col-12">
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="modalVariationTrackBatch">
                                    <label class="form-check-label" for="modalVariationTrackBatch">Track Batch</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="modalVariationTrackExpiry">
                                    <label class="form-check-label" for="modalVariationTrackExpiry">Track
                                        Expiry</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <hr>
                            <label class="form-label fw-semibold">Attributes</label>
                            <div class="d-flex gap-2 mb-2">
                                <input type="text" class="form-control form-control-sm" id="modalAttrKey"
                                    placeholder="Key e.g. Color">
                                <input type="text" class="form-control form-control-sm" id="modalAttrValue"
                                    placeholder="Value e.g. Red">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="modalAddAttrBtn">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </div>
                            <div class="attribute-container" id="modalAttrContainer"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveVariationBtn">
                    <i class="fa fa-save me-1"></i> Save Variation
                </button>
            </div>
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
    @if(isset($product))
    var selectedCategoryId = "{{ $product->category_id }}";
    var selectedBrandId = "{{ $product->brand_id }}";
    var selectedSubCategoryId = "{{ $product->sub_category_id }}";
    $('#business_id').trigger('change');
    @else
    var selectedCategoryId = "";
    var selectedSubCategoryId = "";
    var selectedBrandId = "";
    @endif

    // ======================================================
    // DATA STORES
    // ======================================================
    var features = [];
    var variations = [];

    // ======================================================
    // INITIALIZE DATA FROM EXISTING PRODUCT
    // ======================================================
    @if(isset($product))

    @foreach($product->productFeatures as $feature)
    features.push({
        name: '{{ $feature->name }}',
        description: '{{ $feature->description }}'
    });
    @endforeach
    @foreach($product->productVariations as $index => $var)
    variations.push({
        product_variation_id: "{{$var->product_variation_id}}",
        name: "{{ $var->name }}",
        sku: '{{ $var->sku }}',
        barcode: "{{ $var->barcode ?? '' }}",
        purchase_price: {{$var->purchase_price ?? 0}},
        sale_price: {{$var->sale_price ?? 0}},
        minimum_stock: {{$var->minimum_stock ?? 0}},
        base_unit_id: "{{$var->base_unit_id ?? 0}}",
        track_batch: {{$var->track_batch ? 'true' : 'false'}},
        track_expiry: {{$var->track_expiry ? 'true' : 'false'}},
        attributes: @json(
        $var->attributes->pluck('value', 'name')
    )
    });
    @endforeach
    console.log(variations);
    @endif


    // ======================================================
    // FEATURE FUNCTIONS
    // ======================================================

    function renderFeatures() {
        const list = document.getElementById('featureList');
        const count = document.getElementById('featureCount');

        list.innerHTML = '';
        count.textContent = features.length;

        if (features.length === 0) {
            list.innerHTML =
                '<span class="text-muted" style="font-size:0.85rem;padding:0.25rem 0;"><i class="fa fa-plus-circle me-1"></i>Add features above</span>';
            return;
        }

        features.forEach((feature, index) => {
            const badge = document.createElement('span');
            badge.className = 'feature-badge';
            badge.dataset.index = index;
            badge.innerHTML = `
                    <span class="fw-semibold">${escapeHtml(feature.name)}</span>
                    <span class="feature-description">· ${escapeHtml(feature.description)}</span>
                    <button type="button" class="remove-feature-btn" data-index="${index}" title="Remove feature">×</button>
                `;

            badge.querySelector('.remove-feature-btn').addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                removeFeature(idx);
            });

            list.appendChild(badge);
        });
    }

    function removeFeature(index) {
        features.splice(index, 1);
        renderFeatures();
    }

    function addFeature(name, description) {
        if (!name.trim() || !description.trim()) {
            errorMessage('Please fill both feature name and description');
            return false;
        }

        if (features.some(f => f.name.toLowerCase() === name.trim().toLowerCase() &&
                f.description.toLowerCase() === description.trim().toLowerCase())) {
            errorMessage('This feature already exists');
            return false;
        }

        features.push({
            name: name.trim(),
            description: description.trim()
        });
        renderFeatures();

        document.getElementById('featureNameInput').value = '';
        document.getElementById('featureDescriptionInput').value = '';
        document.getElementById('featureNameInput').focus();

        return true;
    }

    // ======================================================
    // VARIATION FUNCTIONS
    // ======================================================

    function renderVariations() {
        const tbody = document.getElementById('variationTableBody');
        const count = document.getElementById('variationCount');

        tbody.innerHTML = '';
        count.textContent = variations.length;

        if (variations.length === 0) {
            tbody.innerHTML = `
                    <tr id="noVariationRow">
                        <td colspan="5" class="text-center text-muted py-3">
                            <i class="fa fa-plus-circle me-1"></i> Click "Add Variation" to get started
                        </td>
                    </tr>
                `;
            return;
        }

        variations.forEach((variation, index) => {
            const tr = document.createElement('tr');
            const attrDisplay = Object.entries(variation.attributes || {})
                .map(([key, value]) => `${key}: ${value}`)
                .join(', ');

            tr.innerHTML = `
                    <td><span class="fw-semibold">${escapeHtml(variation.name)}</span></td>
                    <td><code>${escapeHtml(variation.sku)}</code></td>
                    <td>$${parseFloat(variation.sale_price || 0).toFixed(2)}</td>
                    <td>${attrDisplay ? `<span class="text-muted small">${escapeHtml(attrDisplay)}</span>` : '<span class="text-muted small">No attributes</span>'}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary edit-variation-btn" data-index="${index}" title="Edit">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-outline-danger delete-variation-btn" data-index="${index}" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </td>
                `;

            tr.querySelector('.edit-variation-btn').addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                openEditVariationModal(idx);
            });

            tr.querySelector('.delete-variation-btn').addEventListener('click', function() {
                const idx = parseInt(this.dataset.index);
                deleteVariation(idx);
            });

            tbody.appendChild(tr);
        });
    }

    function saveVariationFromModal() {

        const type = document.getElementById('productType').value;
        const editIndex = document.getElementById('editVariationIndex').value;

        if (
            type === 'single' &&
            editIndex === '' &&
            variations.length >= 1
        ) {
            errorMessage('Single product can have only one variation');
            return;
        }
        const name = document.getElementById('modalVariationName').value.trim();
        const sku = document.getElementById('modalVariationSku').value.trim();
        const barcode = document.getElementById('modalVariationBarcode').value.trim();
        const unit = document.getElementById('modalVariationUnit').value;
        const purchasePrice = parseFloat(document.getElementById('modalVariationPurchasePrice').value) || 0;
        const salePrice = parseFloat(document.getElementById('modalVariationSalePrice').value) || 0;
        const minStock = parseInt(document.getElementById('modalVariationMinStock').value) || 0;
        const trackBatch = document.getElementById('modalVariationTrackBatch').checked;
        const trackExpiry = document.getElementById('modalVariationTrackExpiry').checked;

        const attrElements = document.querySelectorAll('#modalAttrContainer .attribute-badge');
        const attributes = {};
        attrElements.forEach(el => {
            const key = el.dataset.key;
            const value = el.dataset.value;
            if (key && value) {
                attributes[key] = value;
            }
        });

        if (!name) {
            errorMessage('Variation name is required');
            return;
        }
        if (!sku) {
            errorMessage('SKU is required');
            return;
        }
        if (!unit) {
            errorMessage('Unit is required');
            return;
        }
        if (salePrice < 0) {
            errorMessage('Sale price must be a positive number');
            return;
        }

        const editId = document.getElementById('editVariationId').value;

        const variationData = {
            product_variaiton_id: editId || null,
            name: name,
            sku: sku,
            barcode: barcode || '',
            purchase_price: purchasePrice,
            sale_price: salePrice,
            minimum_stock: minStock,
            base_unit_id: unit,
            track_batch: trackBatch,
            track_expiry: trackExpiry,
            attributes: attributes
        };

        if (editIndex !== '') {
            const idx = parseInt(editIndex);
            variations[idx] = {
                ...variations[idx],
                ...variationData
            };
        } else {
            variations.push(variationData);
        }

        renderVariations();
        resetVariationModal();
        const modal = bootstrap.Modal.getInstance(document.getElementById('variationModal'));
        modal.hide();
    }

    function openEditVariationModal(index) {
        const variation = variations[index];
        if (!variation) return;

        document.getElementById('editVariationIndex').value = index;
        document.getElementById('editVariationId').value = variation.id || '';

        document.getElementById('modalVariationName').value = variation.name || '';
        document.getElementById('modalVariationSku').value = variation.sku || '';
        document.getElementById('modalVariationBarcode').value = variation.barcode || '';
        document.getElementById('modalVariationUnit').value = variation.base_unit_id || '';
        document.getElementById('modalVariationPurchasePrice').value = variation.purchase_price || 0;
        document.getElementById('modalVariationSalePrice').value = variation.sale_price || 0;
        document.getElementById('modalVariationMinStock').value = variation.minimum_stock || 0;
        document.getElementById('modalVariationTrackBatch').checked = variation.track_batch || false;
        document.getElementById('modalVariationTrackExpiry').checked = variation.track_expiry || false;

        const container = document.getElementById('modalAttrContainer');
        container.innerHTML = '';
        const attrs = variation.attributes || {};
        Object.entries(attrs).forEach(([key, value]) => {
            addAttributeBadgeToModal(key, value);
        });

        document.getElementById('variationModalTitle').innerHTML = `<i class="fa fa-pencil me-1"></i> Edit Variation`;
        document.getElementById('saveVariationBtn').innerHTML = `<i class="fa fa-save me-1"></i> Update Variation`;

        const modal = new bootstrap.Modal(document.getElementById('variationModal'));
        modal.show();
    }

    function deleteVariation(index) {
        if (!confirm('Are you sure you want to delete this variation?')) return;
        variations.splice(index, 1);
        renderVariations();
    }

    function resetVariationModal() {
        document.getElementById('editVariationIndex').value = '';
        document.getElementById('editVariationId').value = '';
        document.getElementById('modalVariationName').value = '';
        document.getElementById('modalVariationSku').value = '';
        document.getElementById('modalVariationBarcode').value = '';
        document.getElementById('modalVariationUnit').value = '';
        document.getElementById('modalVariationPurchasePrice').value = '0.00';
        document.getElementById('modalVariationSalePrice').value = '0.00';
        document.getElementById('modalVariationMinStock').value = '0';
        document.getElementById('modalVariationTrackBatch').checked = false;
        document.getElementById('modalVariationTrackExpiry').checked = false;
        document.getElementById('modalAttrContainer').innerHTML = '';
        document.getElementById('modalAttrKey').value = '';
        document.getElementById('modalAttrValue').value = '';
        document.getElementById('variationModalTitle').innerHTML =
            `<i class="fa fa-plus-circle me-1"></i> Add Variation`;
        document.getElementById('saveVariationBtn').innerHTML = `<i class="fa fa-save me-1"></i> Save Variation`;
    }

    function addAttributeBadgeToModal(key, value) {
        const container = document.getElementById('modalAttrContainer');
        const badge = document.createElement('span');
        badge.className = 'attribute-badge';
        badge.dataset.key = key;
        badge.dataset.value = value;
        badge.innerHTML = `
                ${escapeHtml(key)}: ${escapeHtml(value)}
                <button type="button" class="remove-attr-btn" title="Remove attribute">×</button>
            `;
        badge.querySelector('.remove-attr-btn').addEventListener('click', function() {
            badge.remove();
        });
        container.appendChild(badge);
    }

    // ======================================================
    // UTILITY FUNCTIONS
    // ======================================================

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // ======================================================
    // FEATURES SECTION VISIBILITY
    // ======================================================

    function toggleFeaturesSection() {
        const isFeatured = document.getElementById('featured').checked;
        const featuresCard = document.getElementById('featuresCard');

        if (isFeatured) {
            featuresCard.classList.remove('features-section-hidden');
        } else {
            featuresCard.classList.add('features-section-hidden');
        }
    }

    // ======================================================
    // EVENT LISTENERS
    // ======================================================

    document.getElementById('productName').addEventListener('input', function() {
        const slug = this.value.toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        document.getElementById('productSlug').value = slug;
    });

    function toggleVariationSection() {
        const type = document.getElementById('productType').value;
        const btn = document.getElementById('openVariationModalBtn');

        
            btn.style.display = 'inline-block';
    }

    document.getElementById('productType').addEventListener('change', toggleVariationSection);
    document.getElementById('featured').addEventListener('change', toggleFeaturesSection);


    // --- Feature Add ---
    document.getElementById('addFeatureBtn').addEventListener('click', function() {
        addFeature(document.getElementById('featureNameInput').value, document.getElementById(
            'featureDescriptionInput').value);
    });

    document.getElementById('featureNameInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('featureDescriptionInput').focus();
        }
    });

    document.getElementById('featureDescriptionInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('addFeatureBtn').click();
        }
    });

    // --- Variation Modal ---
    document.getElementById('openVariationModalBtn').addEventListener('click', function() {

        const type = document.getElementById('productType').value;

        if (type === 'single' && variations.length >= 1) {
            errorMessage('Single product can have only one variation');
            return;
        }

        resetVariationModal();

        const modal = new bootstrap.Modal(document.getElementById('variationModal'));
        modal.show();
        });

    document.getElementById('modalAddAttrBtn').addEventListener('click', function() {
        const key = document.getElementById('modalAttrKey').value.trim();
        const value = document.getElementById('modalAttrValue').value.trim();

        if (!key || !value) {
            errorMessage('Both attribute key and value are required');
            return;
        }

        const existingKeys = document.querySelectorAll('#modalAttrContainer .attribute-badge');
        for (const el of existingKeys) {
            if (el.dataset.key.toLowerCase() === key.toLowerCase()) {
                errorMessage(`Attribute "${key}" already exists in this variation`);
                return;
            }
        }

        addAttributeBadgeToModal(key, value);
        document.getElementById('modalAttrKey').value = '';
        document.getElementById('modalAttrValue').value = '';
        document.getElementById('modalAttrKey').focus();
    });

    document.getElementById('modalAttrKey').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('modalAttrValue').focus();
        }
    });

    document.getElementById('modalAttrValue').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('modalAddAttrBtn').click();
        }
    });

    document.getElementById('saveVariationBtn').addEventListener('click', saveVariationFromModal);

    // --- Form Submit ---
    document.getElementById('productForm').addEventListener('submit', function(e) {
        // For create mode

        const type = document.getElementById('productType').value;
        if (type === 'variable' && variations.length === 0) {
            e.preventDefault();
            errorMessage('Please add at least one variation for variable products');
            return false;
        }

        // Store data in hidden fields for submission
        // Features data
        document.querySelectorAll('input[name="features[]"]').forEach(el => el.remove());
        features.forEach((feature) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'features[]';
            input.value = JSON.stringify(feature);
            document.getElementById('productForm').appendChild(input);
        });

        // Variations data
        document.querySelectorAll('input[name="variations[]"]').forEach(el => el.remove());
        variations.forEach((variation) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'variations[]';
            input.value = JSON.stringify(variation);
            document.getElementById('productForm').appendChild(input);
        });

        document.getElementById('saveProductBtn').disabled = true;
        document.getElementById('saveProductBtn').innerHTML =
            '<i class="fa fa-spinner fa-spin me-1"></i> Saving...';
    });

    // ======================================================
    // INITIAL RENDER
    // ======================================================

    renderFeatures();
    renderVariations();
    toggleFeaturesSection();
    toggleVariationSection();

    // ======================================================
    // BUSINESS/CATEGORY DYNAMIC LOADING
    // ======================================================

    $('#business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#category_id').html('<option value="">--Select Category--</option>');
            $('#brand_id').html('<option value="">--Select Brand--</option>');
            return;
        }

        ajaxRequest({
            url: url_local + '/admin/category/by-business/' + business_id,
            data: {}
        }).then((response) => {
            let data = response.Data;
            let options = '<option value="">--Select Category--</option>';
            $.each(data, function(index, item) {
                options += `<option value="${item.category_id}" ${item.category_id == selectedCategoryId ? 'selected' : ''}>${item.name}</option>`;
            });
            $('#category_id').html(options).val(selectedCategoryId);
            if (selectedCategoryId) {
                $('#category_id').trigger('change');
            }
        }).catch((err) => {
            errorMessage(err.Message);
        });
        ajaxRequest({
            url: url_local + '/admin/brands/by-business/' + business_id,
            data: {}
        }).then((response) => {
            let data = response.Data;
            let options = '<option value="">--Select Brand--</option>';
            $.each(data, function(index, item) {
                options += `<option value="${item.brand_id}" ${item.brand_id == selectedBrandId ? 'selected' : ''}>${item.name}</option>`;
            });
            $('#brand_id').html(options).val(selectedBrandId);
            if (selectedBrandId) {
                $('#brand_id').trigger('change');
            }
        }).catch((err) => {
            errorMessage(err.Message);
        });
    });

    $('#category_id').change(function() {
        let category_id = $(this).val();
        if (!category_id) {
            $('#sub_category_id').html('<option value="">--Select Sub Category--</option>');
            return;
        }
        ajaxRequest({
            url: url_local + '/admin/sub-category/by-category/' + category_id,
            data: {}
        }).then((response) => {
            let data = response.Data;
            let options = '<option value="">--Select Sub Category--</option>';
            $.each(data, function(index, item) {
                options += `<option value="${item.sub_category_id}" ${item.sub_category_id == selectedSubCategoryId ? 'selected' : ''}>${item.name}</option>`;
            });
            $('#sub_category_id').html(options).val(selectedSubCategoryId);
            if (selectedSubCategoryId) {
                $('#sub_category_id').trigger('change');
            }
        }).catch((err) => {
            errorMessage(err.Message);
        });
    });

</script>
@endsection