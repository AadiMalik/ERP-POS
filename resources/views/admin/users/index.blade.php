@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Admin Users
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <a href="{{ url('admin/users/create') }}" class="btn btn-primary rounded-pill">
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
                                            {{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select id="branch_id" class="form-select">
                                <option value="">--All Branches--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->branch_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Role</label>
                            <select id="role_id" class="form-select">
                                <option value="">--All Roles--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($roles as $item)
                                        <option value="{{ $item->id }}">{{ $item->name ?? '' }}</option>
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
                    <table id="users_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Business</th>
                                <th>Branch</th>
                                <th>Status</th>
                                <th>Last Login</th>
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
                                    {data:'name',name:'name'},
                                    {data:'email',name:'email'},
                                    {data:'phone',name:'phone'},
                                    {data:'role',name:'role',orderable:false},
                                    {data:'business',name:'business'},
                                    {data:'branch',name:'branch'},
                                    {data:'status',name:'status'},
                                    {data:'last_login_at',name:'last_login_at'},
                                    {data:'action',name:'action',sortable:false}",
        'route' => 'users/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'users_table',
        'variable' => 'users_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),role_id:$('#role_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#branch_id').select2();
            $('#role_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableusers_table();
        });
        $('#business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                $('#branch_id').html('<option value="">--All Branches--</option>');
                $('#role_id').html('<option value="">--All Roles--</option>');
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/branch/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--Select Branch--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.branch_id}">
                                        ${item.name}
                                    </option>
                                    `;
                    });
                    $('#branch_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
            // roles
            ajaxRequest({
                    url: url_local + '/admin/roles/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--Select Role--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.id}">
                                        ${item.name}
                                    </option>
                                    `;
                    });
                    $('#role_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });
        //status
        updateStatus({
            buttonClass: ".statusUser",
            url: url_local + "/admin/users/change-status",
            tableCallback: function() {
                initDataTableusers_table();
            }
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteUser",
            url: url_local + "/admin/users",

            tableCallback: function() {
                initDataTableusers_table();
            }
        });

        $('#reset_filter').click(function() {
            // normal selects
            $('#business_id').val('').trigger('change');
            $('#branch_id').val('').trigger('change');
            $('#role_id').val('').trigger('change');
            // date filter
            $('#date_filter').val('').trigger('change');
            // custom date
            $('#start_date').val('');
            $('#end_date').val('');
            // global variables
            filterStartDate = '';
            filterEndDate = '';
            // selected date text hide
            $('#selected_date_range').html('').hide();
            // reload table
            initDataTableusers_table(); // ya users_table
        });
    </script>
@endsection
