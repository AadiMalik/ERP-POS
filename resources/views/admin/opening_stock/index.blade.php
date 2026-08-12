@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Opening Stock
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <a href="{{ url('admin/opening-stock/create') }}" class="btn btn-primary rounded-pill">
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
                    <table id="opening_stock_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Opening Stock No.</th>
                                <th>Date</th>
                                <th>Warehouse</th>
                                <th>Products</th>
                                <th>Total Value</th>
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
                        {data:'opening_stock_no',name:'opening_stock_no'},
                        {data:'opening_stock_date',name:'opening_stock_date'},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total_value',name:'total_value'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'opening-stock/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'opening_stock_table',
        'variable' => 'opening_stock_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTableopening_stock_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let opening_stock_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/opening-stock/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    opening_stock_id: opening_stock_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTableopening_stock_table();
                },
                error: function() {

                    errorMessage(error.Message || 'Something went wrong.');
                    initDataTableopening_stock_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deleteOpeningStock",
            url: url_local + "/admin/opening-stock",

            tableCallback: function() {
                initDataTableopening_stock_table();
            }
        });
    </script>
@endsection
