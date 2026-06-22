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
            Products
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <a href="{{ url('admin/product/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    Add New
                </a>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($businesses as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label class="form-label">Brand</label>
                            <select id="brand_id" class="form-select">
                                <option value="">--All Brands--</option>
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
                            <label class="form-label">Category</label>
                            <select id="category_id" class="form-select">
                                <option value="">--All Categories--</option>
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
                            <label class="form-label">Sub Category</label>
                            <select id="sub_category_id" class="form-select">
                                <option value="">--All Sub Categories--</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="product_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Brand</th>
                                <th>Type</th>
                                <th>Variations</th>
                                <th>Images</th>
                                <th>Features</th>
                                <th>Business</th>
                                <th>Status</th>
                                <th>Action</th>
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
                        <h5>Product Variations</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
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
                            <i class="fa fa-images me-2"></i> Product Images
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        {{-- Upload Section --}}
                        <div class="border rounded p-3 mb-4 bg-light">
                            <h6 class="mb-3">Upload New Images</h6>
                            <input type="file" id="product_images_input" class="form-control mb-2" multiple
                                accept="image/*">
                            <div class="form-text mb-2">JPG, PNG, WEBP — max 2MB each.</div>
                            <div id="upload_preview" class="d-flex flex-wrap gap-2 mb-3"></div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" id="upload_images_btn" class="btn btn-primary ms-auto">
                            <i class="fa fa-upload me-1"></i> Upload
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
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
            $('#business_id').select2();
            $('#brand_id').select2();
            $('#category_id').select2();
            $('#sub_category_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableproduct_table();
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
                $('#category_id').html('<option value="">--All Categories--</option>');
                $('#brand_id').html('<option value="">--All Brands--</option>');
                return;
            }

            ajaxRequest({
                url: url_local + '/admin/category/by-business/' + business_id,
                data: {}
            }).then((response) => {
                let data = response.Data;
                let options = '<option value="">--All Categories--</option>';
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
                let options = '<option value="">--All Brands--</option>';
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
