@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Transfer Notes
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <a href="{{ url('admin/transfer-note/create') }}" class="btn btn-primary rounded-pill">
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
                            <label class="form-label">Source Warehouse</label>
                            <select id="source_warehouse_id" class="form-select">
                                <option value="">--All Warehouses--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Destination Warehouse</label>
                            <select id="destination_warehouse_id" class="form-select">
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
                    <table id="transfer_note_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Transfer No.</th>
                                <th>Date</th>
                                <th>Source Warehouse</th>
                                <th>Destination Warehouse</th>
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
                        {data:'transfer_note_no',name:'transfer_note_no'},
                        {data:'transfer_note_date',name:'transfer_note_date'},
                        {data:'source_warehouse',name:'source_warehouse',sortable:false},
                        {data:'destination_warehouse',name:'destination_warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total_value',name:'total_value'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'transfer-note/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'transfer_note_table',
        'variable' => 'transfer_note_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),source_warehouse_id:$('#source_warehouse_id').val(),destination_warehouse_id:$('#destination_warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#source_warehouse_id').select2();
            $('#destination_warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTabletransfer_note_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let transfer_note_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/transfer-note/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    transfer_note_id: transfer_note_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTabletransfer_note_table();
                },
                error: function() {

                    errorMessage(error.Message || 'Something went wrong.');
                    initDataTabletransfer_note_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deleteTransferNote",
            url: url_local + "/admin/transfer-note",

            tableCallback: function() {
                initDataTabletransfer_note_table();
            }
        });
    </script>
@endsection
