@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Modules</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap align-items-center"></div>
            <a href="javascript:void(0)" id="createIntroModule" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i>Add New</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_modules_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.modules.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_modules.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},{data:'category',name:'category'},{data:'display_order',name:'display_order'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'modules/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_modules_table',
'variable' => 'intro_modules_table',
])

@endsection