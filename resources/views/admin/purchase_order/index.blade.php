@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Purchase Orders
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <a href="{{ url('admin/purchase-order/create') }}" class="btn btn-primary rounded-pill">
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
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        {{-- <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select id="branch_id" class="form-select">
                                <option value="">--All Branches--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($branch as $item)
                                        <option value="{{ $item->branch_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div> --}}
                        <div class="col-md-3">
                            <label class="form-label">Supplier</label>
                            <select id="supplier_id" class="form-select">
                                <option value="">--All Suppliers--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($suppliers as $item)
                                        <option value="{{ $item->supplier_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Warehouse</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">--All Warehouses--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($warehouses as $item)
                                        <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All Statuses--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($statuses as $value => $label)
                                        <option value="{{ $value }}">{{ $label ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
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
                    <table id="purchase_order_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Order No.</th>
                                <th>Order Date</th>
                                <th>Supplier</th>
                                <th>Warehouse</th>
                                <th>Products</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Business</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                    {data:'purchase_order_no',name:'purchase_order_no'},
                    {data:'purchase_order_date',name:'purchase_order_date'},
                    {data:'supplier',name:'supplier',sortable:false},
                    {data:'warehouse',name:'warehouse',sortable:false},
                    {data:'total_products',name:'total_products',sortable:false},
                    {data:'total',name:'total'},
                    {data:'status',name:'status',sortable:false},
                    {data:'business',name:'business',sortable:false},
                    {data:'action',name:'action',sortable:false}",
        'route' => 'purchase-order/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'purchase_order_table',
        'variable' => 'purchase_order_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),supplier_id:$('#supplier_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#supplier_id').select2();
            $('#warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTablepurchase_order_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let purchase_order_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/purchase-order/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    purchase_order_id: purchase_order_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTablepurchase_order_table();
                },
                error: function() {

                    errorMessage(error.Message || 'Something went wrong.');
                    initDataTablepurchase_order_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deletePurchaseOrder",
            url: url_local + "/admin/purchase-order",

            tableCallback: function() {
                initDataTablepurchase_order_table();
            }
        });

        $('#business_id').on('change', function() {

            let business_id = $(this).val();

            // Reset dropdowns
            $('#branch_id').html('<option value="">--Select Branch--</option>');
            $('#supplier_id').html('<option value="">--Select Supplier--</option>');
            $('#warehouse_id').html('<option value="">--Select Warehouse--</option>');

            if (!business_id) {
                return;
            }

            Promise.all([
                    ajaxRequest({
                        url: url_local + '/admin/branch/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/supplier/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/warehouse/by-business/' + business_id,
                        data: {}
                    })
                ])
                .then(([branchRes, supplierRes, warehouseRes, productRes]) => {

                    // Branches
                    let branchOptions = '<option value="">--Select Branch--</option>';
                    $.each(branchRes.Data, function(_, item) {
                        branchOptions += `<option value="${item.branch_id}">
                                ${item.code} ${item.name}
                              </option>`;
                    });
                    $('#branch_id').html(branchOptions);

                    // Suppliers
                    let supplierOptions = '<option value="">--Select Supplier--</option>';
                    $.each(supplierRes.Data, function(_, item) {
                        supplierOptions += `<option value="${item.supplier_id}">
                                    ${item.code} ${item.name}
                                </option>`;
                    });
                    $('#supplier_id').html(supplierOptions);

                    // Warehouses
                    let warehouseOptions = '<option value="">--Select Warehouse--</option>';
                    $.each(warehouseRes.Data, function(_, item) {
                        warehouseOptions += `<option value="${item.warehouse_id}">
                                    ${item.code} ${item.name}
                                </option>`;
                    });
                    $('#warehouse_id').html(warehouseOptions);

                })
                .catch((err) => {
                    errorMessage(err.Message ?? 'Something went wrong.');
                });

        });
    </script>
@endsection
