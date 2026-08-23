@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Stock Taking
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <a href="{{ url('admin/stock-taking/create') }}" class="btn btn-primary rounded-pill">
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
                    <table id="stock_taking_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Stock Taking No.</th>
                                <th>Date</th>
                                <th>Warehouse</th>
                                <th>Products</th>
                                <th>Difference Value</th>
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
                        {data:'stock_taking_no',name:'stock_taking_no'},
                        {data:'stock_taking_date',name:'stock_taking_date'},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total_difference_value',name:'total_difference_value'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'stock-taking/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'stock_taking_table',
        'variable' => 'stock_taking_table',
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
            initDataTablestock_taking_table();
        });
        //status
        function submitStockTakingStatus(stock_taking_id, status, select, confirmDrift) {
            $.ajax({
                url: url_local + "/admin/stock-taking/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    stock_taking_id: stock_taking_id,
                    status: status,
                    confirm_drift: confirmDrift ? 1 : 0
                },
                success: function(response) {

                    // Approving can come back as a warning rather than a
                    // completed change if live stock has moved since the
                    // count was taken - ask the approver to confirm before
                    // actually posting.
                    if (response.Data && response.Data.requires_confirmation) {
                        let lines = (response.Data.drift || []).map(function(d) {
                            return d.product_name + ': counted against ' + d.counted_system_quantity +
                                ', now ' + d.current_system_quantity;
                        }).join('\n');

                        Swal.fire({
                            title: 'Stock has changed since this count was taken',
                            text: 'The final adjustment will use the CURRENT stock quantity, not what was originally counted:\n' + lines,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Approve anyway'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitStockTakingStatus(stock_taking_id, status, select, true);
                            } else {
                                select.val(select.data('old'));
                            }
                        });
                        return;
                    }

                    successMessage(response.Message);
                    initDataTablestock_taking_table();
                },
                error: function() {

                    errorMessage(error.Message || 'Something went wrong.');
                    initDataTablestock_taking_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });
        }

        $(document).on('change', '.change-status', function() {

            let stock_taking_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            submitStockTakingStatus(stock_taking_id, status, select, false);
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteStockTaking",
            url: url_local + "/admin/stock-taking",

            tableCallback: function() {
                initDataTablestock_taking_table();
            }
        });
    </script>
@endsection
