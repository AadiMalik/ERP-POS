@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Goods Receipt Notes
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <a href="{{ url('admin/good-receipt-note/create') }}" class="btn btn-primary rounded-pill">
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
                        <div class="col-md-3">
                            <label class="form-label">Purchase</label>
                            <select id="purchase_id" class="form-select">
                                <option value="">--All Purchases--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($purchases as $item)
                                        <option value="{{ $item->purchase_id }}">{{ $item->purchase_no ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
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
                    <table id="grn_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>GRN No.</th>
                                <th>GRN Date</th>
                                <th>Purchase No.</th>
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
                        {data:'good_receipt_note_no',name:'good_receipt_note_no'},
                        {data:'good_receipt_note_date',name:'good_receipt_note_date'},
                        {data:'purchase_no',name:'purchase_no',sortable:false},
                        {data:'supplier',name:'supplier',sortable:false},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total',name:'total'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'good-receipt-note/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'grn_table',
        'variable' => 'grn_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),purchase_id:$('#purchase_id').val(),supplier_id:$('#supplier_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#purchase_id').select2();
            $('#supplier_id').select2();
            $('#warehouse_id').select2();
            $('#status').select2();
        });

        $('#toggleFilter').click(function() {
            $('#filterSection').slideToggle();
        });

        $('#search_btn').click(function() {
            initDataTablegrn_table();
        });

        $('#reset_filter').click(function() {
            $('#business_id, #purchase_id, #supplier_id, #warehouse_id, #status').val(null).trigger('change');
            initDataTablegrn_table();
        });

        //status
        $(document).on('change', '.change-status', function() {

            let good_receipt_note_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/good-receipt-note/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    good_receipt_note_id: good_receipt_note_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTablegrn_table();
                },
                error: function(error) {

                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                    initDataTablegrn_table();
                    select.val(select.data('old'));
                }
            });

        });

        //delete
        deleteRecord({
            buttonClass: "#deleteGoodReceiptNote",
            url: url_local + "/admin/good-receipt-note",

            tableCallback: function() {
                initDataTablegrn_table();
            }
        });

        $('#business_id').on('change', function() {

            let business_id = $(this).val();

            $('#supplier_id').html('<option value="">--All Suppliers--</option>');
            $('#warehouse_id').html('<option value="">--All Warehouses--</option>');
            $('#purchase_id').html('<option value="">--All Purchases--</option>');

            if (!business_id) {
                return;
            }

            Promise.all([
                    ajaxRequest({
                        url: url_local + '/admin/supplier/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/warehouse/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/purchase/by-business/' + business_id,
                        data: {}
                    })
                ])
                .then(([supplierRes, warehouseRes, purchaseRes]) => {

                    let supplierOptions = '<option value="">--All Suppliers--</option>';
                    $.each(supplierRes.Data, function(_, item) {
                        supplierOptions += `<option value="${item.supplier_id}">
                                    ${item.code} ${item.name}
                                </option>`;
                    });
                    $('#supplier_id').html(supplierOptions);

                    let warehouseOptions = '<option value="">--All Warehouses--</option>';
                    $.each(warehouseRes.Data, function(_, item) {
                        warehouseOptions += `<option value="${item.warehouse_id}">
                                    ${item.name}
                                </option>`;
                    });
                    $('#warehouse_id').html(warehouseOptions);

                    let purchaseOptions = '<option value="">--All Purchases--</option>';
                    $.each(purchaseRes.Data, function(_, item) {
                        if (item.purchase_type == 'purchase_request') {
                            purchaseOptions += `<option value="${item.purchase_id}">
                                    ${item.purchase_no}
                                </option>`;
                        }
                    });
                    $('#purchase_id').html(purchaseOptions);

                })
                .catch((err) => {
                    errorMessage(err.Message ?? 'Something went wrong.');
                });

        });
    </script>
@endsection
