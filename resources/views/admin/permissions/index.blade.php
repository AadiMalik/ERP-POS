@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <section class="table-components">
        <div class="container-fluid">

            <!-- ========== tables-wrapper start ========== -->
            <div class="tables-wrapper mt-50">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card-style mb-30">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="mb-10">Permissions</h6>
                                </div>
                                <div class="col-md-6 text-right">
                                    <a href="javascript:void(0)" id="createNewPermission"
                                        class="main-btn primary-btn btn-hover btn-sm">
                                        <i class="lni lni-plus mr-5"></i>Add New</a>
                                </div>
                            </div>
                            <hr>
                            <div class="table-wrapper table-responsive">
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
                        <!-- end card -->
                    </div>
                    <!-- end col -->
                </div>
                <!-- end row -->
            </div>
            <!-- ========== tables-wrapper end ========== -->
        </div>
        <!-- end container -->
    </section>
    <!-- ========== table components end ========== -->
    @include('admin/permissions/model/create')
@endsection
@section('js')
    <script src="{{ asset('assets/js/admin/permission.js') }}"></script>
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
