@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Purchase Requests
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <div class="d-flex gap-2">
                    @include('admin.partials.import-export-buttons', [
                        'importExportModule' => 'purchase-request',
                        'importExportLabel' => 'Purchase Requests',
                        'importExportRefreshFn' => 'initDataTablepurchase_request_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/purchase-request/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        Add New
                    </a>
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
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}
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
                <div class="table-responsive p-4">
                    <table id="purchase_request_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Request No.</th>
                                <th>Request Date</th>
                                <th>Supplier</th>
                                <th>Warehouse</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Business</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            @include('admin/purchase_request/model/send')
            @include('admin.partials.import-export-modal')
        </div>
    @endsection
    @section('js')
        @include('admin.partials.datatable', [
            'columns' => "
        {data:'purchase_request_no',name:'purchase_request_no'},
        {data:'purchase_request_date',name:'purchase_request_date'},
        {data:'supplier',name:'supplier',sortable:false},
        {data:'warehouse',name:'warehouse',sortable:false},
        {data:'total_products',name:'total_products',sortable:false},
        {data:'status',name:'status',sortable:false},
        {data:'business',name:'business',sortable:false},
        {data:'action',name:'action',sortable:false}",
            'route' => 'purchase-request/data',
            'buttons' => false,
            'pageLength' => 10,
            'class' => 'purchase_request_table',
            'variable' => 'purchase_request_table',
            'datefilter' => true,
            'params' =>
                "business_id:$('#business_id').val(),supplier_id:$('#supplier_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
        ])

        <script>
            $(document).ready(function() {
                $('#business_id').select2();
                $('#supplier_id').select2();
                $('#supplier_ids').select2({
                    dropdownParent: $('#ajaxModel'),
                    width: '100%'
                });
                $('#warehouse_id').select2();
                $('#status').select2();
            });
            $('#search_btn').click(function() {
                initDataTablepurchase_request_table();
            });
            //status
            $(document).on('change', '.change-status', function() {

                let purchase_request_id = $(this).data('id');
                let status = $(this).val();
                let select = $(this);

                $.ajax({
                    url: url_local + "/admin/purchase-request/change-status", // route
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        purchase_request_id: purchase_request_id,
                        status: status
                    },
                    success: function(response) {

                        successMessage(response.Message);
                        initDataTablepurchase_request_table();
                    },
                    error: function() {

                        errorMessage(error.Message || 'Something went wrong.');
                        initDataTablepurchase_request_table();
                        // Previous value restore
                        select.val(select.data('old'));
                    }
                });

            });
            //delete
            deleteRecord({
                buttonClass: "#deletePurchaseRequest",
                url: url_local + "/admin/purchase-request",

                tableCallback: function() {
                    initDataTablepurchase_request_table();
                }
            });

            $('#business_id').on('change', function() {

                let business_id = $(this).val();

                // Reset dropdowns
                $('#branch_id').html('<option value="">--All Branches--</option>');
                $('#supplier_id').html('<option value="">--All Suppliers--</option>');
                $('#warehouse_id').html('<option value="">--All Warehouses--</option>');

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
                        let branchOptions = '<option value="">--All Branches--</option>';
                        $.each(branchRes.Data, function(_, item) {
                            branchOptions += `<option value="${item.branch_id}">
                                ${item.code} ${item.name}
                              </option>`;
                        });
                        $('#branch_id').html(branchOptions);

                        // Suppliers
                        let supplierOptions = '<option value="">--All Suppliers--</option>';
                        $.each(supplierRes.Data, function(_, item) {
                            supplierOptions += `<option value="${item.supplier_id}">
                                    ${item.code} ${item.name}
                                </option>`;
                        });
                        $('#supplier_id').html(supplierOptions);

                        // Warehouses
                        let warehouseOptions = '<option value="">--All Warehouses--</option>';
                        $.each(warehouseRes.Data, function(_, item) {
                            warehouseOptions += `<option value="${item.warehouse_id}">
                                    ${item.name}
                                </option>`;
                        });
                        $('#warehouse_id').html(warehouseOptions);

                    })
                    .catch((err) => {
                        errorMessage(err.Message ?? 'Something went wrong.');
                    });

            });

            $(document).on('click', '.sendQuotation', function() {
                let purchase_request_id = $(this).data('id');
                $('#purchase_request_id').val(purchase_request_id);
                $('#ajaxModel').modal('show');
            });

            $(document).off('click', '#btnSendQuotation').on('click', '#btnSendQuotation', function() {
                let btn = $(this);
                $.ajax({
                    url: "{{ url('admin/purchase-request/send-quotation') }}",
                    type: "POST",
                    data: {
                        purchase_request_id: $('#purchase_request_id').val(),
                        supplier_ids: $('#supplier_ids').val(),
                        send_email: $('#send_email').is(':checked') ? 1 : 0,
                        send_whatsapp: $('#send_whatsapp').is(':checked') ? 1 : 0,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        btn.prop('disabled', true);
                        showLoader();
                    },
                    success: function(response) {
                        if (response.Status) {
                            successMessage(response.Message);
                            $('#ajaxModel').modal('hide');
                            $('#supplier_ids').val('').trigger('change');
                        } else {
                            errorMessage(response.Message);
                        }

                    },

                    complete: function() {
                        btn.prop('disabled', false);
                        hideLoader();
                    },
                    error: function(xhr) {
                        btn.prop('disabled', false);
                        hideLoader();
                        if (xhr.status == 422) {

                            $.each(xhr.responseJSON.errors, function(key, value) {
                                errorMessage(value[0]);
                            });
                        } else {
                            errorMessage(xhr.responseJSON.Message);
                        }
                    }
                });
            });
        </script>
    @endsection
