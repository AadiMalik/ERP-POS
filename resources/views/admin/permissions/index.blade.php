@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"> Permissions</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5></h5>
                <a href="javascript:void(0)" id="createNewPermission" class="btn rounded-pill btn-primary">
                    <i class="icon-base fa fa-plus mr-5"></i>Add New</a>
            </div>
            <div class="table-responsive text-nowrap p-4">
                <table id="permission_table" class="table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Permission</th>
                            <th>Is System</th>
                            <th>Action</th>
                        </tr>
                        <!-- end table row-->
                    </thead>
                    <tbody>
                    </tbody>
                </table>
                <!-- end table -->
            </div>
        </div>
    @include('admin/permissions/model/create')
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    <script src="{{ asset('public/assets/js/admin/permission.js') }}"></script>
    @include('admin.partials.datatable', [
        'columns' => "
                                                        {data: 'name' , name: 'name'},
                                                        {data: 'is_system_only' , name: 'is_system_only', 'sortable': false , searchable: false},
                                                        {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'permissions-data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'permission_table',
        'variable' => 'permission_table',
    ])
@endsection
