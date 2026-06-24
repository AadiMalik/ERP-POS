@php
    use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Product Variation Batches</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <a href="javascript:void(0)" id="createNewProductVariationBatch"
                    class="btn rounded-pill btn-primary">
                    <i class="icon-base fa fa-plus mr-5"></i>Add New</a>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="filter_business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Product</label>
                            <select id="filter_product_id" class="form-select">
                                <option value="">--All Products--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($products as $item)
                                        <option value="{{ $item->product_id }}">
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Product Variation</label>
                            <select id="filter_product_variation_id" class="form-select">
                                <option value="">--All Product Variations--</option>

                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Warehouse</label>
                            <select id="filter_warehouse_id" class="form-select">
                                <option value="">--All Warehouses--</option>
                                @foreach ($warehouse as $item)
                                    <option value="{{ $item->warehouse_id }}">
                                        {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
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
                <div class="table-responsive text-nowrap p-4">
                    <table id="product_variation_batch_table" class="table display datatables" style="width:100%">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Warehouse</th>
                                <th>Avg Cost</th>
                                <th>Quantity</th>
                                <th>Manufacturer</th>
                                <th>Expiry</th>
                                <th>Business</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                            <!-- end table row-->
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <!-- end table -->
                </div>
            </div>
        </div>
        @include('admin/variation_batch/model/create')
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    <script src="{{ asset('public/assets/js/admin/product_variation_batch.js') }}"></script>
    @include('admin.partials.datatable', [
        'columns' => "
        {data: 'product' , name: 'product', 'sortable': false , searchable: false},
        {data: 'productVariation' , name: 'productVariation', 'sortable': false , searchable: false},
        {data: 'warehouse' , name: 'warehouse', 'sortable': false , searchable: false},
        {data: 'avg_price' , name: 'avg_price'},
        {data: 'quantity' , name: 'quantity'},
        {data: 'manufacturer_date' , name: 'manufacturer_date'},
        {data: 'expiry_date' , name: 'expiry_date'},
        {data: 'business' , name: 'business', 'sortable': false , searchable: false},
        {data: 'status' , name: 'status', 'sortable': false , searchable: false},
        {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'product-variation-batch/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'product_variation_batch_table',
        'variable' => 'product_variation_batch_table',
        'datefilter' => true,
        'params' => "business_id:$('#filter_business_id').val(),product_id:$('#filter_product_id').val(),product_variation_id:$('#filter_product_variation_id').val(),warehouse_id:$('#filter_warehouse_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#product_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#product_variation_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#warehouse_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#filter_business_id').select2();
            $('#filter_product_id').select2();
            $('#filter_product_variation_id').select2();
            $('#filter_warehouse_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableproduct_variation_batch_table();
        });
        $('#business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                $('#product_id').html('<option value="">--Select Product--</option>');
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/product/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--Select Product--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.product_id}">
                                        ${item.name}
                                    </option>
                                    `;
                    });
                    $('#product_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });
        $('#filter_business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                $('#filter_product_id').html('<option value="">--All Products--</option>');
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/product/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--All Products--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.product_id}">
                                        ${item.name}
                                    </option>
                                    `;
                    });
                    $('#filter_product_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });

        //product
        $('#product_id').change(function() {
            let product_id = $(this).val();
            if (!product_id) {
                $('#product_variation_id').html('<option value="">--Select Product Variation--</option>');
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/product/variation-by-product/' + product_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--Select Product Variation--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.product_variation_id}">
                                        ${item.name}
                                    </option>
                                    `;
                    });
                    $('#product_variation_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });
        $('#filter_product_id').change(function() {
            let product_id = $(this).val();
            if (!product_id) {
                $('#filter_product_variation_id').html('<option value="">--All Product Variations--</option>');
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/product/variation-by-product/' + product_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--All Product Variations--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.product_variation_id}">
                                        ${item.name}
                                    </option>
                                    `;
                    });
                    $('#filter_product_variation_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });
    </script>
@endsection
