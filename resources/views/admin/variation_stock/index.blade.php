@php
    use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Product Variation Stocks</h4>

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
                    <table id="product_variation_stock_table" class="table display datatables" style="width:100%">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Warehouse</th>
                                <th>Avg Cost</th>
                                <th>Quantity</th>
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
    </div>

    <!-- Stock History Modal -->
    <div class="modal fade" id="stockHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Stock History
                        <small class="d-block text-muted fw-normal" id="stockHistorySubtitle"></small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-primary d-flex justify-content-between align-items-center">
                        <span>Current Available Balance</span>
                        <strong id="stockHistoryCurrentBalance">0.00</strong>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Date / Time</th>
                                    <th>Source Module</th>
                                    <th>Reference No</th>
                                    <th>Type</th>
                                    <th>In / Out</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Running Balance</th>
                                </tr>
                            </thead>
                            <tbody id="stockHistoryRows">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
            {data: 'product' , name: 'product', 'sortable': false , searchable: false},
            {data: 'productVariation' , name: 'productVariation', 'sortable': false , searchable: false},
            {data: 'warehouse' , name: 'warehouse', 'sortable': false , searchable: false},
            {data: 'avg_price' , name: 'avg_price'},
            {data: 'quantity' , name: 'quantity'},
            {data: 'business' , name: 'business', 'sortable': false , searchable: false},
            {data: 'status' , name: 'status', 'sortable': false , searchable: false},
            {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'product-variation-stock/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'product_variation_stock_table',
        'variable' => 'product_variation_stock_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#filter_business_id').val(),product_id:$('#filter_product_id').val(),product_variation_id:$('#filter_product_variation_id').val(),warehouse_id:$('#filter_warehouse_id').val()",
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
            initDataTableproduct_variation_stock_table();
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

        updateStatus({
            buttonClass: ".statusProductVariationStock",
            url: url_local + "/admin/product-variation-stock/change-status",
            tableCallback: function() {
                initDataTableproduct_variation_stock_table();
            }
        });


        deleteRecord({
            buttonClass: "#deleteProductVariationStock",
            url: url_local + "/admin/product-variation-stock",

            tableCallback: function() {
                initDataTableproduct_variation_stock_table();
            }
        });

        // Stock History
        $(document).off('click', '#viewStockHistory').on('click', '#viewStockHistory', function() {
            let product_variation_stock_id = $(this).data('id');

            $('#stockHistoryRows').html(
                '<tr><td colspan="7" class="text-center"><div class="spinner-border spinner-border-sm"></div> Loading...</td></tr>'
            );
            $('#stockHistorySubtitle').text('');
            $('#stockHistoryCurrentBalance').text('0.00');
            $('#stockHistoryModal').modal('show');

            $.ajax({
                url: url_local + '/admin/product-variation-stock/history/' + product_variation_stock_id,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (!response.Success) {
                        errorMessage(response.Message);
                        return;
                    }

                    let data = response.Data;

                    $('#stockHistorySubtitle').text(
                        data.product + ' - ' + data.product_variation + ' (' + data.warehouse + ')'
                    );
                    $('#stockHistoryCurrentBalance').text(decimal(data.current_balance));

                    if (!data.ledger.length) {
                        $('#stockHistoryRows').html(
                            '<tr><td colspan="7" class="text-center text-muted">No stock movements found.</td></tr>'
                        );
                        return;
                    }

                    let rows = '';
                    $.each(data.ledger, function(_, item) {
                        let directionBadge = item.direction == 'in' ?
                            '<span class="badge bg-success">Stock In</span>' :
                            '<span class="badge bg-danger">Stock Out</span>';
                        let sign = item.direction == 'in' ? '+' : '-';

                        rows += `
                            <tr>
                                <td>${item.transaction_date}</td>
                                <td>${item.source_module}</td>
                                <td>${item.reference_no}</td>
                                <td>${item.transaction_type}</td>
                                <td>${directionBadge}</td>
                                <td class="text-end">${sign}${decimal(item.quantity)} ${item.unit}</td>
                                <td class="text-end fw-semibold">${decimal(item.running_balance)}</td>
                            </tr>
                        `;
                    });

                    $('#stockHistoryRows').html(rows);
                },
                error: function() {
                    $('#stockHistoryRows').html(
                        '<tr><td colspan="7" class="text-center text-danger">Unable to load stock history.</td></tr>'
                    );
                }
            });
        });
    </script>
@endsection
