@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Businesses
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5></h5>
                <a href="{{ url('admin/business/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    Add New
                </a>
            </div>
            <div class="table-responsive p-4">
                <table id="business_table" class="table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Package</th>
                            <th>Phone</th>
                            <th>Sub. Start</th>
                            <th>Sub. End</th>
                            <th>Rem. Days</th>
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
        {data:'owner_name',name:'owner_name'},
        {data:'email',name:'email'},
        {data:'package',name:'package'},
        {data:'phone',name:'phone'},
        {data:'subscription_start',name:'subscription_start'},
        {data:'subscription_end',name:'subscription_end'},
        {data:'remaining_days',name:'remaining_days', searchable:false, orderable:false},
        {data:'status',name:'status'},
        {data:'action',name:'action',sortable:false}",
        'route' => 'business/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'business_table',
        'variable' => 'business_table',
    ])

    <script>
        
        //delete
        deleteRecord({
            buttonClass: "#deleteBusiness",
            url: url_local + "/admin/business",

            tableCallback: function() {
                initDataTablebusiness_table();
            }
        });
    </script>
@endsection
