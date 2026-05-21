@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"> Roles</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header">
                @if (RoleNames::SUPERADMIN == getRoleName())
                    <a href="{{ url('admin/roles/create') }}" class="btn rounded-pill btn-primary">
                        <i class="icon-base bx bx-plus mr-5"></i>Add New</a>
                @endif
            </div>
            <div class="table-responsive text-nowrap p-4">
                <table id="role_table" class="table display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Description</th>
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
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
            {data: 'name' , name: 'name'},
            {data: 'description' , name: 'description'},
            {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'roles-data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'role_table',
        'variable' => 'role_table',
    ])
@endsection
