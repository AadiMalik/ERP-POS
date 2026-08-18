@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Leave Types</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            @can('leave-type.create')
            <a href="{{ url('admin/leave-type/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                Add New
            </a>
            @endcan
        </div>
        <div class="card-body">
            <div class="table-responsive p-4">
                <table id="leave_type_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Max Days/Year</th>
                            <th>Paid</th>
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
{data:'code',name:'code'},
{data:'name',name:'name'},
{data:'max_days_per_year',name:'max_days_per_year'},
{data:'is_paid',name:'is_paid'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'leave-type/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'leave_type_table',
'variable' => 'leave_type_table',
'datefilter' => false,
'params' => "",
])
<script>
    deleteRecord({
        buttonClass: "#deleteLeaveType",
        url: url_local + "/admin/leave-type",
        tableCallback: function() {
            initDataTableleave_type_table();
        }
    });
</script>
@endsection
