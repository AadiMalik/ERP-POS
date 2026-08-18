@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Login History
        </h4>
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
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All Statuses--</option>
                                <option value="success">Success</option>
                                <option value="failed">Failed</option>
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
                    <table id="login_history_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Login At</th>
                                <th>Logout At</th>
                                <th>User</th>
                                <th>Business</th>
                                <th>Status</th>
                                <th>IP Address</th>
                                <th>Device</th>
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
                        {data:'login_at',name:'login_at'},
                        {data:'logout_at',name:'logout_at',sortable:false},
                        {data:'user',name:'user',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'ip_address',name:'ip_address',sortable:false},
                        {data:'device',name:'device',sortable:false}",
        'route' => 'login-history/data',
        'buttons' => false,
        'pageLength' => 25,
        'class' => 'login_history_table',
        'variable' => 'login_history_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTablelogin_history_table();
        });
        $('#reset_filter').click(function() {
            $('#business_id, #status').val('').trigger('change');
            initDataTablelogin_history_table();
        });
    </script>
@endsection
