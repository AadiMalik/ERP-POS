@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Branches
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5></h5>
                <a href="{{ url('admin/branch/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    Add New
                </a>
            </div>
            <div class="table-responsive p-4">
                <table id="branch_table" class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Status</th>
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
                    {data:'code',name:'code'},
                    {data:'name',name:'name'},
                    {data:'email',name:'email'},
                    {data:'phone',name:'phone'},
                    {data:'address',name:'address'},
                    {data:'status',name:'status'},
                    {data:'action',name:'action',sortable:false}",
        'route' => 'branch-data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'branch_table',
        'variable' => 'branch_table',
    ])

    <script>
        //delete
        deleteRecord({
            buttonClass: "#deleteBranch",
            url: url_local + "/admin/branch",

            tableCallback: function() {
                initDataTablebranch_table();
            }
        });
    </script>
@endsection
