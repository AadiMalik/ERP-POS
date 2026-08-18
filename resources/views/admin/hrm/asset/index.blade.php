@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Assets</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            @can('asset.create')
            <a href="{{ url('admin/asset/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                Add New
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="asset_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Tag</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Condition</th>
                            <th>Allocated To</th>
                            <th>Status</th>
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
{data:'asset_tag',name:'asset_tag'},
{data:'name',name:'name'},
{data:'category',name:'category'},
{data:'condition',name:'condition'},
{data:'allocated_to',name:'allocated_to',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'asset/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'asset_table',
'variable' => 'asset_table',
'datefilter' => false,
'params' => "",
])
<script>
    deleteRecord({
        buttonClass: "#deleteAsset",
        url: url_local + "/admin/asset",
        tableCallback: function() {
            initDataTableasset_table();
        }
    });
</script>
@endsection
