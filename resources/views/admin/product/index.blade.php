@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('css')
    <style>
        .modal {
            z-index: 1055 !important;
        }

        .modal-backdrop {
            z-index: 1050 !important;
        }
    </style>
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('products.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                    <button type="button" id="btnBackfillBarcodes" class="btn btn-outline-secondary">
                        <i class="fa fa-barcode"></i>
                        {{ __('products.backfill_barcodes') }}
                    </button>

                </div>
                <div class="d-flex gap-2">
                    @include('admin.partials.import-export-buttons', [
                        'importExportModule' => 'product',
                        'importExportLabel' => 'Products',
                        'importExportRefreshFn' => 'initDataTableproduct_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/product/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        {{ __('common.add_new') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('products.business') }}</label>
                                <select id="business_id" class="form-select">
                                    <option value="">{{ __('products.all_businesses') }}</option>
                                    @foreach ($businesses as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label class="form-label">{{ __('products.brand') }}</label>
                            <select id="brand_id" class="form-select">
                                <option value="">{{ __('products.all_brands') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($brands as $item)
                                        <option value="{{ $item->brand_id }}">
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ __('products.category') }}</label>
                            <select id="category_id" class="form-select">
                                <option value="">{{ __('products.all_categories') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($categories as $item)
                                        <option value="{{ $item->category_id }}">
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ __('products.sub_category') }}</label>
                            <select id="sub_category_id" class="form-select">
                                <option value="">{{ __('products.all_sub_categories') }}</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">{{ __('products.date') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                {{ __('common.search') }}
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                {{ __('common.reset') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="product_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('products.col_name') }}</th>
                                <th>{{ __('products.col_category') }}</th>
                                <th>{{ __('products.col_brand') }}</th>
                                <th>{{ __('products.col_type') }}</th>
                                <th>{{ __('products.col_variations') }}</th>
                                <th>{{ __('products.col_images') }}</th>
                                <th>{{ __('products.col_features') }}</th>
                                <th>{{ __('products.col_business') }}</th>
                                <th>{{ __('products.col_status') }}</th>
                                <th>{{ __('products.col_action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        {{-- variation modal --}}
        <div class="modal fade" id="variationModal">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5>{{ __('products.variations_modal_title') }}</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                    </div>

                </div>
            </div>
        </div>

        {{-- variation price history modal --}}
        <div class="modal fade" id="priceHistoryModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5>{{ __('products.price_history_modal_title') }}</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('products.date') }}</th>
                                        <th>{{ __('products.col_sale_type') }}</th>
                                        <th>{{ __('products.col_old_price') }}</th>
                                        <th>{{ __('products.col_new_price') }}</th>
                                        <th>{{ __('products.col_changed_by') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="priceHistoryTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- images modal --}}
        <div class="modal fade" id="imageModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa fa-images me-2"></i> {{ __('products.images_modal_title') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        {{-- Upload Section --}}
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h6 class="mb-3">{{ __('products.upload_new_images') }}</h6>
                            <input type="file" id="product_images_input" class="form-control mb-2" multiple
                                accept="image/*">
                            <div class="form-text mb-2">{{ __('products.image_upload_hint') }}</div>
                            <div id="upload_preview" class="d-flex flex-wrap gap-2 mb-3"></div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" id="upload_images_btn" class="btn btn-primary ms-auto">
                            <i class="fa fa-upload me-1"></i> {{ __('products.upload') }}
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            {{ __('common.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @include('admin.partials.import-export-modal')
    </div>
@endsection
@section('js')
    @php
        $__i18nProducts = [
            'backfill_confirm_title' => __('products.backfill_confirm_title'),
            'backfill_confirm_text' => __('products.backfill_confirm_text'),
            'backfill_confirm_button' => __('products.backfill_confirm_button'),
            'backfill_failed' => __('products.backfill_failed'),
            'all_categories' => __('products.all_categories'),
            'all_brands' => __('products.all_brands'),
        ];
    @endphp
    <script>
        window.i18n_products = @json($__i18nProducts);
    </script>
    @if (session('error'))
        <script>
            errorMessage("{{ session('error') }}");
        </script>
    @endif
    @if (session('sucess'))
        <script>
            sucessMessage("{{ session('sucess') }}");
        </script>
    @endif
    @include('admin.partials.datatable', [
        'columns' => "
                                    {data:'name',name:'name'},
                                    {data:'category',name:'category',sortable:false},
                                    {data:'brand',name:'brand',sortable:false},
                                    {data:'type',name:'type'},
                                    {data:'variations',name:'variations',sortable:false},
                                    {data:'images',name:'images',sortable:false},
                                    {data:'features',name:'features',sortable:false},
                                    {data:'business',name:'business',sortable:false},
                                    {data:'status',name:'status',sortable:false},
                                    {data:'action',name:'action',sortable:false}",
        'route' => 'product/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'product_table',
        'variable' => 'product_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),brand_id:$('#brand_id').val(),category_id:$('#category_id').val(),sub_category_id:$('sub_category_id').val()",
    ])
    <script>
        $(document).ready(function() {
            $('#business_id').select2({ language: CURRENT_LOCALE !== 'en' ? CURRENT_LOCALE : undefined });
            $('#brand_id').select2({ language: CURRENT_LOCALE !== 'en' ? CURRENT_LOCALE : undefined });
            $('#category_id').select2({ language: CURRENT_LOCALE !== 'en' ? CURRENT_LOCALE : undefined });
            $('#sub_category_id').select2({ language: CURRENT_LOCALE !== 'en' ? CURRENT_LOCALE : undefined });
        });
        $('#search_btn').click(function() {
            initDataTableproduct_table();
        });

        // backfill barcodes/qr codes for existing variations that don't have one yet
        $('#btnBackfillBarcodes').click(function() {
            Swal.fire({
                title: window.i18n_products?.backfill_confirm_title || "Backfill missing barcodes/QR codes?",
                text: window.i18n_products?.backfill_confirm_text || "This only fills in variations that don't have a barcode yet. Existing barcodes are never changed.",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: window.i18n_products?.backfill_confirm_button || "Yes, backfill",
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: url_local + "/admin/product/barcode/backfill",
                        method: "POST",
                    }).then((response) => {
                        successMessage(response.Message);
                    }).catch((err) => {
                        errorMessage(err.Message || window.i18n_products?.backfill_failed || "Backfill failed");
                    });
                }
            });
        });
        //status
        updateStatus({
            buttonClass: ".statusProduct",
            url: url_local + "/admin/product/change-status",
            tableCallback: function() {
                initDataTableproduct_table();
            }
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteProduct",
            url: url_local + "/admin/product",

            tableCallback: function() {
                initDataTableproduct_table();
            }
        });

        $('#business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                $('#category_id').html('<option value="">' + window.i18n_products.all_categories + '</option>');
                $('#brand_id').html('<option value="">' + window.i18n_products.all_brands + '</option>');
                return;
            }

            ajaxRequest({
                url: url_local + '/admin/category/by-business/' + business_id,
                data: {}
            }).then((response) => {
                let data = response.Data;
                let options = '<option value="">' + window.i18n_products.all_categories + '</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.category_id}">${item.name}</option>`;
                });
                $('#category_id').html(options);
            }).catch((err) => {
                errorMessage(err.Message);
            });

            ajaxRequest({
                url: url_local + '/admin/brands/by-business/' + business_id,
                data: {}
            }).then((response) => {
                let data = response.Data;
                let options = '<option value="">' + window.i18n_products.all_brands + '</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.brand_id}">${item.name}</option>`;
                });
                $('#brand_id').html(options);
            }).catch((err) => {
                errorMessage(err.Message);
            });
        });

        $('#category_id').change(function() {
            let category_id = $(this).val();
            if (!category_id) {
                $('#sub_category_id').html('<option value="">--All Sub Categories--</option>');
                return;
            }
            ajaxRequest({
                url: url_local + '/admin/sub-category/by-category/' + category_id,
                data: {}
            }).then((response) => {
                let data = response.Data;
                let options = '<option value="">--All Sub Categories--</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.sub_category_id}">${item.name}</option>`;
                });
                $('#sub_category_id').html(options);
            }).catch((err) => {
                errorMessage(err.Message);
            });
        });

        //variations
        $(document).on('click', '.view-variations', function() {

            let product_id = $(this).data('id');
            $('#variationModal').data('product-id', product_id);

            ajaxRequest({
                url: url_local + '/admin/product/variations/' + product_id,
                method: 'GET'
            }).then((res) => {
                var data = res.Data;
                $('#variationModal .modal-body').html(data);
                $('#variationModal').modal('show');

            }).catch((err) => {
                errorMessage(err.Message);
            });

        });


        //status variation
        updateStatus({
            buttonClass: ".toggle-variation",
            url: url_local + "/admin/product/variation/status",
            tableCallback: function() {
                initDataTableproduct_table();
            }
        });

        //delete variation
        deleteRecord({
            buttonClass: ".delete-variation",
            url: url_local + "/admin/product/variation/delete",

            tableCallback: function() {
                $('#variationModal').modal('hide');
                initDataTableproduct_table();
            }
        });

        //regenerate barcode/qr code
        $(document).on('click', '.regenerate-barcode', function() {
            let product_variation_id = $(this).data('id');
            let isManual = $(this).data('is-manual') == 1;
            let productId = $('#variationModal').data('product-id');

            Swal.fire({
                title: "Regenerate barcode/QR code?",
                text: isManual ?
                    "This variation has a manufacturer-provided barcode. Regenerating will replace it with a system-generated one." :
                    "This will replace the current barcode/QR code with a new one.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, regenerate",
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: url_local + "/admin/barcode/regenerate",
                        method: "POST",
                        data: {
                            product_variation_ids: [product_variation_id],
                            overwrite_manual: isManual ? 1 : 0,
                        },
                    }).then((response) => {
                        successMessage(response.Message);

                        ajaxRequest({
                            url: url_local + '/admin/product/variations/' + productId,
                            method: 'GET'
                        }).then((res) => {
                            $('#variationModal .modal-body').html(res.Data);
                        });
                    }).catch((err) => {
                        errorMessage(err.Message || "Regenerate failed");
                    });
                }
            });
        });


        //variation price history
        $(document).on('click', '.view-price-history', function() {
            let product_variation_id = $(this).data('id');

            ajaxRequest({
                url: url_local + '/admin/product/variation-price-history/' + product_variation_id,
                method: 'GET'
            }).then((res) => {
                let rows = '';

                (res.Data || []).forEach(function(item) {
                    rows += `<tr>
                        <td>${item.date_created}</td>
                        <td>${item.sale_type_name}</td>
                        <td>${item.old_price}</td>
                        <td>${item.new_price}</td>
                        <td>${item.changed_by}</td>
                    </tr>`;
                });

                $('#priceHistoryTableBody').html(rows || '<tr><td colspan="5" class="text-center">No price changes recorded yet.</td></tr>');
                $('#priceHistoryModal').modal('show');
            }).catch((err) => {
                errorMessage(err.Message);
            });
        });

        //product images

        // ── Open modal ──────────────────────────────────────────────────
        $(document).on('click', '.add-image', function() {
            let currentProductId = $(this).data('id');
            $('#imageModal').data('product-id', currentProductId);
            $('#product_images_input').val('');
            $('#upload_preview').html('');
            $('#upload_set_default').prop('checked', false);
            $('#imageModal').modal('show');
        });


        // ── Set default ─────────────────────────────────────────────────
        $(document).on('click', '.set-default-btn', function() {
            let id = $(this).data('id');
            ajaxRequest({
                url: url_local + '/admin/product/image/set-default/' + id,
                method: 'POST'
            }).then(() => {
                // Update UI: remove all active, set this one
                $('.set-default-btn')
                    .removeClass('btn-warning')
                    .addClass('btn-outline-secondary');
                $(this).removeClass('btn-outline-secondary').addClass('btn-warning');
                initDataTableproduct_table();
            }).catch((err) => errorMessage(err.Message));
        });

        // ── Delete from modal ───────────────────────────────────────────
        // ── Thumbnail X button (datatable column se direct delete) ──────
        deleteRecord({
            buttonClass: ".delete-image",
            url: url_local + "/admin/product/image/delete",

            tableCallback: function() {
                $('#imageModal').modal('hide');
                initDataTableproduct_table();
            }
        });

        // ── Upload preview ──────────────────────────────────────────────
        $('#product_images_input').off('change').on('change', function () {
            let container = $('#upload_preview');
            container.html('');
            Array.from(this.files).forEach(file => {
                let reader = new FileReader();
                reader.onload = function(e) {
                    container.append(
                        `<img src="${e.target.result}" class="rounded border"
                      style="width:60px; height:60px; object-fit:cover;">`
                    );
                };
                reader.readAsDataURL(file);
            });
        });

        // ── Upload submit ───────────────────────────────────────────────
        $('#upload_images_btn').click(function() {
            let productId = $('#imageModal').data('product-id');
            let files = $('#product_images_input')[0].files;
            let setDefault = $('#upload_set_default').is(':checked') ? 1 : 0;

            if (!files.length) {
                errorMessage('Please select at least one image.');
                return;
            }

            let formData = new FormData();
            formData.append('product_id', productId);
            formData.append('set_default', setDefault);
            $.each(files, (i, file) => formData.append('images[]', file));

            $.ajax({
                url: url_local + '/admin/product/image/upload',
                method: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    $('#product_images_input').val('');
                    $('#upload_preview').html('');
                    $('#upload_set_default').prop('checked', false);
                    $('#imageModal').modal('hide');
                    initDataTableproduct_table();
                },
                error: function(err) {
                    errorMessage(err.responseJSON?.Message ?? 'Upload failed.');
                }
            });
        });
    </script>
@endsection
