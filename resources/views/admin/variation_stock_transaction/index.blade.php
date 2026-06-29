@php
    use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Product Variation Stock Transactions</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
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
                            <label class="form-label">Transaction Type</label>
                            <select id="transaction_type" class="form-select">
                                <option value="">--All Transaction Types--</option>
                                @foreach ($transaction_types as $value => $item)
                                    <option value="{{ $value }}">
                                        {{ $item ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference Type</label>
                            <select id="reference_type" class="form-select">
                                <option value="">--All Reference Types--</option>
                                @foreach ($reference_types as $value => $item)
                                    <option value="{{ $value }}">
                                        {{ $item ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Batch</label>
                            <select id="product_variation_batch_id" class="form-select">
                                <option value="">--All Batches--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($batches as $item)
                                        <option value="{{ $item->product_variation_batch_id }}">
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Product</label>
                            <select id="product_id" class="form-select">
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
                            <select id="product_variation_id" class="form-select">
                                <option value="">--All Product Variations--</option>

                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Warehouse</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">--All Warehouses--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($warehouse as $item)
                                        <option value="{{ $item->warehouse_id }}">
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Unit</label>
                            <select id="unit_id" class="form-select">
                                <option value="">--All Units--</option>
                                @foreach ($units as $item)
                                    <option value="{{ $item->unit_id }}">
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
                    <table id="product_variation_stock_transaction_table" class="table display datatables"
                        style="width:100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Warehouse</th>
                                <th>Transaction Type</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Unit Price</th>
                                <th>Total Price</th>
                                <th>Balance Quantity</th>
                                <th>Avg Cost</th>
                                <th>Batch</th>
                                <th>Reference</th>
                                <th>Remarks</th>
                                <th>Business</th>
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
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'transaction_date',name:'transaction_date'},
                        {data:'product',name:'product'},
                        {data:'variation',name:'variation'},
                        {data:'warehouse',name:'warehouse'},
                        {data:'transaction_type',name:'transaction_type'},
                        {data:'quantity',name:'quantity'},
                        {data:'unit',name:'unit'},
                        {data:'unit_price',name:'unit_price'},
                        {data:'total_price',name:'total_price'},
                        {data:'balance_qty',name:'balance_qty'},
                        {data:'avg_cost',name:'avg_cost'},
                        {data:'batch',name:'batch'},
                        {data:'reference',name:'reference'},
                        {data:'remarks',name:'remarks'},
                        {data:'business',name:'business',searchable:false,orderable:false},
                        {data:'action',name:'action',searchable:false,orderable:false},",
        'route' => 'product-variation-stock-transaction/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'product_variation_stock_transaction_table',
        'variable' => 'product_variation_stock_transaction_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),transaction_type:$('#transaction_type').val(),reference_type:$('#reference_type').val(),unit_id:$('#unit_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),warehouse_id:$('#warehouse_id').val(),product_variation_batch_id:$('#product_variation_batch_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#product_id').select2();
            $('#product_variation_id').select2();
            $('#warehouse_id').select2();
            $('#transaction_type').select2();
            $('#reference_type').select2();
            $('#unit_id').select2();
            $('#product_variation_batch_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableproduct_variation_stock_transaction_table();
        });
        $('#business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                $('#product_id').html('<option value="">--All Products--</option>');
                $('#warehouse_id').html('<option value="">--All Warehouses--</option>');
                $('#product_variation_batch_id').html('<option value="">--All Batches--</option>');
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
                    $('#product_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });

            ajaxRequest({
                    url: url_local + '/admin/warehouse/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--All Warehouses--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.warehouse_id}">
                                        ${item.name}
                                    </option>
                                    `;
                    });
                    $('#warehouse_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });

            ajaxRequest({
                    url: url_local + '/admin/product-variation-batch/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--All Batches--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.product_variation_batch_id}">
                                        ${item.name}
                                    </option>
                                    `;
                    });
                    $('#product_variation_batch_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });

        });

        //product
        $('#product_id').change(function() {
            let product_id = $(this).val();
            if (!product_id) {
                $('#product_variation_id').html('<option value="">--All Product Variations--</option>');
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
                    $('#product_variation_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });


        deleteRecord({
            buttonClass: "#deleteProductVariationStockTransaction",
            url: url_local + "/admin/product-variation-stock-transaction",

            tableCallback: function() {
                initDataTableproduct_variation_stock_transaction_table();
            }
        });
    </script>
@endsection
