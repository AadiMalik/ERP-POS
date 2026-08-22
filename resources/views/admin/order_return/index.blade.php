@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Order Returns
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                @canAccess('order-return.create')
                    <a href="{{ url('admin/order-return/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        Add New
                    </a>
                @endcanAccess
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
                            <label class="form-label">Customer</label>
                            <select id="customer_id" class="form-select">
                                <option value="">--All Customers--</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->user->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Warehouse</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">--All Warehouses--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}
                                    </option>
                                @endforeach
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
                    <table id="order_return_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Return No.</th>
                                <th>Return Date</th>
                                <th>Order No.</th>
                                <th>Customer</th>
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
                        {data:'order_return_no',name:'order_return_no'},
                        {data:'order_return_date',name:'order_return_date'},
                        {data:'order_no',name:'order_no',sortable:false},
                        {data:'customer',name:'customer',sortable:false},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total',name:'total'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'order-return/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'order_return_table',
        'variable' => 'order_return_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),customer_id:$('#customer_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#customer_id').select2();
            $('#warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTableorder_return_table();
        });
        $('#reset_filter').click(function() {
            $('#business_id').val('').trigger('change.select2');
            $('#customer_id').val('').trigger('change.select2');
            $('#warehouse_id').val('').trigger('change.select2');
            $('#status').val('').trigger('change.select2');
            $('#date_filter').val('').trigger('change');
            filterStartDate = '';
            filterEndDate = '';
            initDataTableorder_return_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let order_return_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/order-return/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    order_return_id: order_return_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTableorder_return_table();
                },
                error: function(error) {

                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                    initDataTableorder_return_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deleteOrderReturn",
            url: url_local + "/admin/order-return",

            tableCallback: function() {
                initDataTableorder_return_table();
            }
        });
    </script>
@endsection
