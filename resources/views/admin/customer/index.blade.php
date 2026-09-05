@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Customers
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
                        'importExportModule' => 'customer',
                        'importExportLabel' => 'Customers',
                        'importExportRefreshFn' => 'initDataTablecustomer_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/customer/create') }}" class="btn btn-primary rounded-pill">
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
                    <table id="customer_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Credit Limit</th>
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
    @include('admin.partials.import-export-modal')
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
    {data:'code',name:'code'},
    {data:'name',name:'name'},
    {data:'email',name:'email'},
    {data:'phone',name:'phone'},
    {data:'credit_limit',name:'credit_limit'},
    {data:'status',name:'status',sortable:false},
    {data:'business',name:'business',sortable:false},
    {data:'action',name:'action',sortable:false}",
        'route' => 'customer/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'customer_table',
        'variable' => 'customer_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTablecustomer_table();
        });
        //status
        updateStatus({
            buttonClass: ".statusCustomer",
            url: url_local + "/admin/customer/change-status",
            tableCallback: function() {
                initDataTablecustomer_table();
            }
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteCustomer",
            url: url_local + "/admin/customer",
            tableCallback: function() {
                initDataTablecustomer_table();
            }
        });
    </script>
@endsection
