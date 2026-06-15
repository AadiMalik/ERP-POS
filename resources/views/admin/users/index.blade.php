@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Admin Users
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5></h5>
                <a href="{{ url('admin/users/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    Add New
                </a>
            </div>
            <div class="table-responsive p-4">
                <table id="users_table" class="table">
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
    ])

    <script>

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
        
    </script>
@endsection
