@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Navigation</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap align-items-center"></div>
            <a href="javascript:void(0)" id="createIntroNav" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i>Add New</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_navigation_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>URL</th>
                            <th>Location</th>
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
    @include('admin.intro.navigation.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_navigation.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'label',name:'label'},{data:'url',name:'url'},{data:'location',name:'location'},{data:'display_order',name:'display_order'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'navigation/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_navigation_table',
'variable' => 'intro_navigation_table',
])

@endsection