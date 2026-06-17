@extends('layouts.app')
@section('css')
    <style>
        .product-edit-card {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
        }

        .product-edit-card .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1rem 1.25rem;
            font-weight: 600;
        }

        .section-header {
            background-color: #f8faff;
            padding: 0.65rem 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            font-weight: 600;
            color: #2c3e50;
            border-left: 4px solid #0d6efd;
        }

        .image-preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
        }

        .image-thumb {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.75rem;
            border: 2px solid #e9ecef;
            transition: 0.2s;
            background: #fff;
        }

        .image-thumb:hover {
            border-color: #0d6efd;
            transform: scale(1.02);
        }

        .image-thumb.default-thumb {
            border-color: #0d6efd;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.3);
        }

        .variation-item {
            background: #f9fbfd;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #eef2f7;
            transition: 0.15s;
        }

        .variation-item:hover {
            background: #f2f6fc;
            border-color: #cdd9e6;
        }

        .attribute-badge {
            background: #e9ecef;
            padding: 0.2rem 0.7rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .attribute-badge .btn-close-attr {
            background: none;
            border: none;
            padding: 0 0.2rem;
            font-size: 0.8rem;
            line-height: 1;
            color: #6c757d;
        }

        .attribute-badge .btn-close-attr:hover {
            color: #b02a37;
        }

        .preview-placeholder {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .sticky-actions {
            position: sticky;
            bottom: 0;
            background: white;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid #dee2e6;
            border-radius: 0 0 1rem 1rem;
            z-index: 10;
        }

        .btn-outline-secondary-custom {
            border-color: #d0d7de;
        }

        .btn-outline-secondary-custom:hover {
            background: #f1f3f5;
        }

        .select2-container .select2-selection--single {
            height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
        }
    </style>
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Product</h4>

        <!-- main card -->
        <div class="card product-edit-card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($product) ? 'Update' : 'New' }} Product</h5>
            </div>

            <!-- form – uses POST for create/update (simulated) -->
            <form id="productForm" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->product_id ?? '' }}">

                <div class="card-body">
                    <div class="row g-4">
                        <!-- ========== LEFT COLUMN ========== -->
                        <div class="col-lg-7">
                            <!-- BASIC INFO -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name"
                                                value="{{ $product->name ?? '' }}" placeholder="e.g. Classic Hoodie">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Slug</label>
                                            <input type="text" class="form-control" name="slug"
                                                value="{{ $product->slug ?? '' }}" placeholder="auto-generated" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                                            <select class="form-select" name="business_id" id="business_id" required>
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
                                            <label class="fw-semibold">Category <span class="text-danger">*</span></label>
                                            <select class="form-select" name="category_id" id="category_id" required>
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
                                            <label class="fw-semibold">Sub Category</label>
                                            <select class="form-select" name="sub_category_id" id="sub_category_id">
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
                                        <div class="col-md-4">
                                            <label class="fw-semibold">Type</label>
                                            <select class="form-select" name="type">
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
                                        <div class="col-md-4">
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
                                        <div class="col-md-4">
                                            <label class="fw-semibold">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="active"
                                                    {{ isset($product) && $product->status == 'active' ? 'selected' : '' }}>
                                                    Active</option>
                                                <option value="inactive"
                                                    {{ isset($product) && $product->status == 'inactive' ? 'selected' : '' }}>
                                                    Inactive</option>
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

                            <!-- SHORT + LONG DESCRIPTION -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">Descriptions</div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="fw-semibold">Short description</label>
                                            <input type="text" class="form-control" name="short_description"
                                                value="{{ $product->short_description ?? '' }}"
                                                placeholder="brief summary">
                                        </div>
                                        <div class="col-12">
                                            <label class="fw-semibold">Full description</label>
                                            <textarea class="form-control" name="description" rows="3" placeholder="detailed description">{{ $product->description ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- PRODUCT IMAGES + PREVIEW -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">Images & preview</div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="fw-semibold">Upload images <span class="text-muted">(JPG,
                                                    PNG)</span></label>
                                            <input type="file" class="form-control" id="productImagesInput"
                                                name="images[]" multiple accept="image/*">
                                            <div class="mt-3 image-preview-grid" id="imagePreviewContainer">
                                                <!-- dynamic previews from JS -->
                                                <div class="preview-placeholder"><i class="fa fa-cloud-upload me-1"></i>
                                                    images will appear here</div>
                                            </div>
                                            <div class="mt-2">
                                                <small class="text-muted"><i
                                                        class="fa fa-info-circle text-success"></i> click image to
                                                    set as default</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- FEATURES (quick add) -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">Features</div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-12 d-flex gap-2">
                                            <input type="text" class="form-control" id="featureName"
                                                placeholder="Feature name e.g. Material">
                                            <input type="text" class="form-control" id="featureDesc"
                                                placeholder="Value e.g. Cotton 100%">
                                            <button type="button" class="btn btn-outline-primary" id="addFeatureBtn"> Add</button>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <div id="featureList" class="d-flex flex-wrap gap-2">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ========== RIGHT COLUMN ========== -->
                        <div class="col-lg-5">
                            <!-- VARIATIONS + ATTRIBUTES -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <span>Variations</span>
                                    <button type="button" class="btn btn-sm btn-primary" id="addVariationBtn"> Add variation</button>
                                </div>
                                <div class="card-body">
                                    <!-- variation container -->
                                    <div id="variationWrapper">
                                        <!-- variation 1 (example) -->
                                        <div class="variation-item mt-3" data-var-id="var1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <h6 class="fw-semibold mb-2"> Variation #1
                                                </h6>
                                                <button type="button" class="btn-close btn-close-sm remove-variation"
                                                    aria-label="Remove"></button>
                                            </div>
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <label class="small fw-semibold">Name</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        name="variations[0][name]" value="" placeholder="Name">
                                                </div>
                                                <div class="col-6">
                                                    <label class="small fw-semibold">SKU</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        name="variations[0][sku]" value="" placeholder="sku">
                                                </div>
                                                <div class="col-6">
                                                    <label class="small fw-semibold">Barcode</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        name="variations[0][barcode]" value=""
                                                        placeholder="barcode">
                                                </div>
                                                <div class="col-6">
                                                    <label class="small fw-semibold">Unit</label>
                                                    <input type="text" class="form-control form-control-sm"
                                                        name="variations[0][base_unit]" value=""
                                                        placeholder="unit">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small fw-semibold">Purchase price</label>
                                                    <input type="number" step="0.01"
                                                        class="form-control form-control-sm"
                                                        name="variations[0][purchase_price]" value="">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small fw-semibold">Sale price</label>
                                                    <input type="number" step="0.01"
                                                        class="form-control form-control-sm"
                                                        name="variations[0][sale_price]" value="">
                                                </div>
                                                <div class="col-4">
                                                    <label class="small fw-semibold">Min stock</label>
                                                    <input type="number" step="1"
                                                        class="form-control form-control-sm"
                                                        name="variations[0][minimum_stock]" value="">
                                                </div>
                                                <div class="col-12">
                                                    <div class="d-flex gap-3 flex-wrap">
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="variations[0][track_batch]" id="trackBatch0">
                                                            <label class="form-check-label small"
                                                                for="trackBatch0">Batch</label>
                                                        </div>
                                                        <div class="form-check form-check-inline">
                                                            <input class="form-check-input" type="checkbox"
                                                                name="variations[0][track_expiry]" id="trackExpiry0">
                                                            <label class="form-check-label small"
                                                                for="trackExpiry0">Expiry</label>
                                                        </div>
                                                        <select class="form-select form-select-sm d-inline-block w-auto"
                                                            name="variations[0][status]">
                                                            <option value="active" selected>Active</option>
                                                            <option value="inactive">Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <!-- attributes for this variation -->
                                                <div class="col-12 mt-2">
                                                    <label class="small fw-semibold">Attributes <span
                                                            class="text-muted">(key:value)</span></label>
                                                    <div class="d-flex gap-1 flex-wrap" id="attrContainer-var1">
                                                    </div>
                                                    <div class="input-group input-group-sm mt-1">
                                                        <input type="text" class="form-control attr-key"
                                                            placeholder="key e.g. Color">
                                                        <input type="text" class="form-control attr-value"
                                                            placeholder="value e.g. Red">
                                                        <button class="btn btn-outline-secondary add-attr-btn"
                                                            type="button">+</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted"><i class="fa fa-info-circle"></i> Each variation can have
                                        multiple attributes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer border-top">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary"
                                onclick="window.history.back()">Cancel</button>
                            <button class="btn btn-primary px-4">Save Product</button>
                        </div>
                    </div>
                </div>

            </form>
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
            errorMessage(
                "{{ session('error') }}"
            );
        </script>
    @endif
    <script>
        (function() {
            // ----- IMAGE PREVIEW (multiple) -----
            const imgInput = document.getElementById('productImagesInput');
            const previewContainer = document.getElementById('imagePreviewContainer');

            // demo default images (simulate existing)
            const demoImages = [];

            function renderDemoImages() {
                previewContainer.innerHTML = '';
                demoImages.forEach((src, idx) => {
                    const div = document.createElement('div');
                    div.style.position = 'relative';
                    div.innerHTML = `
          <img src="${src}" class="image-thumb ${idx === 0 ? 'default-thumb' : ''}" data-default="${idx === 0}" data-src="${src}">
          <span class="badge bg-secondary position-absolute top-0 start-100 translate-middle" style="font-size:0.6rem;">${idx === 0 ? '★' : ''}</span>
        `;
                    div.querySelector('img').addEventListener('click', function(e) {
                        // set default
                        document.querySelectorAll('.image-thumb').forEach(th => th.classList.remove(
                            'default-thumb'));
                        this.classList.add('default-thumb');
                        // update badge
                        document.querySelectorAll('.image-preview-grid .badge').forEach(b => b
                            .textContent = '');
                        this.parentElement.querySelector('.badge').textContent = '★';
                    });
                    previewContainer.appendChild(div);
                });
            }
            renderDemoImages();

            // handle new uploads
            imgInput.addEventListener('change', function(e) {
                const files = Array.from(e.target.files);
                if (files.length === 0) return;
                // clear demo if any
                const placeholders = previewContainer.querySelectorAll('.preview-placeholder');
                placeholders.forEach(p => p.remove());

                files.forEach((file, index) => {
                    if (!file.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        const div = document.createElement('div');
                        div.style.position = 'relative';
                        const isDefault = (index === 0 && !document.querySelector(
                            '.image-thumb.default-thumb'));
                        div.innerHTML = `
            <img src="${ev.target.result}" class="image-thumb ${isDefault ? 'default-thumb' : ''}" data-default="${isDefault}">
            <span class="badge bg-secondary position-absolute top-0 start-100 translate-middle" style="font-size:0.6rem;">${isDefault ? '★' : ''}</span>
          `;
                        div.querySelector('img').addEventListener('click', function() {
                            document.querySelectorAll('.image-thumb').forEach(th => th
                                .classList.remove('default-thumb'));
                            this.classList.add('default-thumb');
                            document.querySelectorAll('.image-preview-grid .badge').forEach(
                                b => b.textContent = '');
                            this.parentElement.querySelector('.badge').textContent = '★';
                        });
                        previewContainer.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
                // reset input so same file can be re-uploaded
                this.value = '';
            });

            // ----- FEATURES (add / remove) -----
            const featureList = document.getElementById('featureList');
            document.getElementById('addFeatureBtn').addEventListener('click', function() {
                const name = document.getElementById('featureName').value.trim();
                const desc = document.getElementById('featureDesc').value.trim();
                if (!name || !desc) {
                    alert('Please fill both name and description');
                    return;
                }
                const badge = document.createElement('span');
                badge.className = 'attribute-badge';
                badge.innerHTML =
                    `${name} <span class="text-muted mx-1">·</span> ${desc} <button type="button" class="btn-close-attr"><i class="fa fa-close"></i></button>`;
                badge.querySelector('.btn-close-attr').addEventListener('click', function() {
                    badge.remove();
                });
                featureList.appendChild(badge);
                document.getElementById('featureName').value = '';
                document.getElementById('featureDesc').value = '';
            });
            // remove feature (existing)
            featureList.querySelectorAll('.btn-close-attr').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.attribute-badge').remove();
                });
            });

            // ----- VARIATIONS: add / remove -----
            let varCounter = 2; // because we have 2 demo
            document.getElementById('addVariationBtn').addEventListener('click', function() {
                const wrapper = document.getElementById('variationWrapper');
                const newVar = document.createElement('div');
                newVar.className = 'variation-item';
                newVar.dataset.varId = 'var' + (++varCounter);
                newVar.innerHTML = `
        <div class="d-flex justify-content-between align-items-start">
          <h6 class="fw-semibold mb-2"><i class="fa fa-tag me-1"></i> Variation #${varCounter}</h6>
          <button type="button" class="btn-close btn-close-sm remove-variation" aria-label="Remove"></button>
        </div>
        <div class="row g-2">
          <div class="col-6"><label class="small fw-semibold">Name</label><input type="text" class="form-control form-control-sm" name="variations[${varCounter-1}][name]" placeholder="e.g. Medium"></div>
          <div class="col-6"><label class="small fw-semibold">SKU</label><input type="text" class="form-control form-control-sm" name="variations[${varCounter-1}][sku]" placeholder="sku"></div>
          <div class="col-6"><label class="small fw-semibold">Barcode</label><input type="text" class="form-control form-control-sm" name="variations[${varCounter-1}][barcode]" placeholder="barcode"></div>
          <div class="col-6"><label class="small fw-semibold">Unit</label><input type="text" class="form-control form-control-sm" name="variations[${varCounter-1}][base_unit]" placeholder="unit"></div>
          <div class="col-4"><label class="small fw-semibold">Purchase price</label><input type="number" step="0.01" class="form-control form-control-sm" name="variations[${varCounter-1}][purchase_price]" value="0.00"></div>
          <div class="col-4"><label class="small fw-semibold">Sale price</label><input type="number" step="0.01" class="form-control form-control-sm" name="variations[${varCounter-1}][sale_price]" value="0.00"></div>
          <div class="col-4"><label class="small fw-semibold">Min stock</label><input type="number" step="1" class="form-control form-control-sm" name="variations[${varCounter-1}][minimum_stock]" value="0"></div>
          <div class="col-12">
            <div class="d-flex gap-3 flex-wrap">
              <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="variations[${varCounter-1}][track_batch]" id="trackBatch${varCounter}"><label class="form-check-label small" for="trackBatch${varCounter}">Batch</label></div>
              <div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="variations[${varCounter-1}][track_expiry]" id="trackExpiry${varCounter}"><label class="form-check-label small" for="trackExpiry${varCounter}">Expiry</label></div>
              <select class="form-select form-select-sm d-inline-block w-auto" name="variations[${varCounter-1}][status]"><option value="active" selected>Active</option><option value="inactive">Inactive</option></select>
            </div>
          </div>
          <div class="col-12 mt-2">
            <label class="small fw-semibold">Attributes</label>
            <div class="d-flex gap-1 flex-wrap" id="attrContainer-var${varCounter}"></div>
            <div class="input-group input-group-sm mt-1">
              <input type="text" class="form-control attr-key" placeholder="key e.g. Color">
              <input type="text" class="form-control attr-value" placeholder="value e.g. Red">
              <button class="btn btn-outline-secondary add-attr-btn" type="button">+</button>
            </div>
          </div>
        </div>
      `;
                // append to wrapper
                wrapper.appendChild(newVar);
                // bind remove variation
                newVar.querySelector('.remove-variation').addEventListener('click', function() {
                    if (document.querySelectorAll('.variation-item').length <= 1) {
                        alert('At least one variation required');
                        return;
                    }
                    newVar.remove();
                });
                // bind add attribute for this variation
                const attrContainer = newVar.querySelector('[id^="attrContainer-"]');
                const addBtn = newVar.querySelector('.add-attr-btn');
                const keyInput = newVar.querySelector('.attr-key');
                const valInput = newVar.querySelector('.attr-value');
                addBtn.addEventListener('click', function() {
                    const key = keyInput.value.trim();
                    const val = valInput.value.trim();
                    if (!key || !val) {
                        alert('Both key and value required');
                        return;
                    }
                    const badge = document.createElement('span');
                    badge.className = 'attribute-badge';
                    badge.innerHTML =
                        `${key}: ${val} <button type="button" class="btn-close-attr"><i class="fa fa-close"></i></button>`;
                    badge.querySelector('.btn-close-attr').addEventListener('click', function() {
                        badge.remove();
                    });
                    attrContainer.appendChild(badge);
                    keyInput.value = '';
                    valInput.value = '';
                });
            });

            // bind remove variation for existing
            document.querySelectorAll('.remove-variation').forEach(btn => {
                btn.addEventListener('click', function() {
                    const item = this.closest('.variation-item');
                    if (document.querySelectorAll('.variation-item').length <= 1) {
                        alert('At least one variation required');
                        return;
                    }
                    item.remove();
                });
            });

            // bind add attribute for existing variations (on page load)
            document.querySelectorAll('.variation-item').forEach(varItem => {
                const attrContainer = varItem.querySelector('[id^="attrContainer-"]');
                const addBtn = varItem.querySelector('.add-attr-btn');
                const keyInput = varItem.querySelector('.attr-key');
                const valInput = varItem.querySelector('.attr-value');
                if (addBtn) {
                    addBtn.addEventListener('click', function() {
                        const key = keyInput.value.trim();
                        const val = valInput.value.trim();
                        if (!key || !val) {
                            alert('Both key and value required');
                            return;
                        }
                        const badge = document.createElement('span');
                        badge.className = 'attribute-badge';
                        badge.innerHTML =
                            `${key}: ${val} <button type="button" class="btn-close-attr"><i class="fa fa-close"></i></button>`;
                        badge.querySelector('.btn-close-attr').addEventListener('click', function() {
                            badge.remove();
                        });
                        attrContainer.appendChild(badge);
                        keyInput.value = '';
                        valInput.value = '';
                    });
                }
                // remove attr button existing
                attrContainer.querySelectorAll('.btn-close-attr').forEach(b => {
                    b.addEventListener('click', function() {
                        this.closest('.attribute-badge').remove();
                    });
                });
            });

            // form submit (prevent default for demo)
            document.getElementById('productForm').addEventListener('submit', function(e) {
                e.preventDefault();
                alert('Product saved successfully! (demo)');
                // you can add actual ajax here
            });

        })();
    </script>
@endsection
